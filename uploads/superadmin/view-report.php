<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['superadmin']);

$db = getDB();
$submissionId = (int) ($_GET['id'] ?? $_POST['submission_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    $postId = (int) ($_POST['submission_id'] ?? 0);

    $postSubmission = getSubmission($db, $postId);
    if (!$postSubmission || !canViewSubmission($postSubmission)) {
        setFlash('danger', 'Report not found.');
        redirect(roleUrl('superadmin', 'submissions.php'));
    }

    if ($action === 'validate' && $postSubmission['status'] === 'submitted') {
        $db->prepare(
            "UPDATE report_submission SET status='validated', validated_by=?, validated_at=NOW(), remarks=?, updated_at=NOW() WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks ?: null, $postId]);
        logAudit($db, 'report_submission', $postId, 'UPDATE', ['status' => 'submitted'], ['status' => 'validated']);
        setFlash('success', 'Report validated successfully.');
    } elseif ($action === 'reject' && $postSubmission['status'] === 'submitted') {
        if ($remarks === '') {
            setFlash('danger', 'Please add a comment explaining why this report is being rejected.');
            redirect(roleUrl('superadmin', 'view-report.php?id=' . $postId));
        }
        $db->prepare(
            "UPDATE report_submission SET status='rejected', validated_by=?, validated_at=NOW(), remarks=?, updated_at=NOW() WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks, $postId]);
        logAudit($db, 'report_submission', $postId, 'UPDATE', ['status' => 'submitted'], ['status' => 'rejected', 'remarks' => $remarks]);
        setFlash('success', 'Report rejected. The nurse can revise and resubmit.');
    } else {
        setFlash('danger', 'This report cannot be reviewed in its current status.');
    }

    redirect(roleUrl('superadmin', 'view-report.php?id=' . $postId));
}

$submission = getSubmission($db, $submissionId);

if (!$submission || !canViewSubmission($submission)) {
    setFlash('danger', 'Report not found.');
    redirect(roleUrl('superadmin', 'submissions.php'));
}

$submittedBy = null;
if (!empty($submission['submitted_by'])) {
    $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
    $stmt->execute([(int) $submission['submitted_by']]);
    $submittedBy = $stmt->fetch();
}

$pageTitle = 'View Report — ' . $submission['program_name'];
$grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
$stored  = loadIndicatorValues($db, $submissionId);

$partsList = [];
$totalFields = 0;
foreach ($grouped as $part => $categories) {
    $count = 0;
    foreach ($categories as $inds) {
        $count += count($inds);
    }
    $partsList[] = ['name' => $part, 'count' => $count];
    $totalFields += $count;
}
$totalParts = count($partsList);
$recordedTotals = sumRecordedIndicatorValues($grouped, $stored);
$grandRecordedTotal = $recordedTotals['grand'];
$partRecordedTotals = $recordedTotals['parts'];

require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saBreadcrumb([
    ['label' => 'Submissions', 'url' => roleUrl('superadmin', 'submissions.php'), 'icon' => 'bi-arrow-left'],
    ['label' => 'View Report'],
]);
saHeader(
    $submission['program_name'],
    'Review and edit monthly health indicators for this submission',
    statusBadge($submission['status'])
);

$metaItems = [
    ['icon' => 'bi-upc-scan', 'label' => 'Report Code', 'value_html' => !empty($submission['report_code'])
        ? '<code>' . e($submission['report_code']) . '</code>'
        : '<span class="text-muted">Not assigned yet</span>'],
    ['icon' => 'bi-geo-alt-fill', 'label' => 'Barangay', 'value' => $submission['barangay_name']],
    ['icon' => 'bi-calendar3', 'label' => 'Reporting Period', 'value' => periodLabel((int) $submission['year'], (int) $submission['month'])],
    ['icon' => 'bi-clipboard2-pulse', 'label' => 'Program', 'value' => $submission['program_name']],
];
if ($submittedBy) {
    $metaItems[] = [
        'icon' => 'bi-person-fill',
        'label' => 'Submitted By',
        'value_html' => e($submittedBy['full_name']) . ($submittedBy['position'] ? '<div class="small text-muted fw-normal">' . e($submittedBy['position']) . '</div>' : ''),
    ];
}
$metaItems[] = [
    'icon' => 'bi-calculator',
    'label' => 'Total Recorded',
    'value_html' => '<span id="recordedGrandTotal">' . e(formatRecordedTotal($grandRecordedTotal)) . '</span>'
        . '<div class="small text-muted fw-normal">Sum of all entered counts</div>',
];
saMetaGrid($metaItems);
?>

<?php if ($submission['status'] === 'submitted'): ?>
<section class="sa-report-review" aria-labelledby="reportReviewHeading">
    <div class="sa-report-review__header">
        <div>
            <h2 class="sa-report-review__title" id="reportReviewHeading">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i> Review submission
            </h2>
            <p class="sa-report-review__subtitle">Approve this report or return it to the nurse with comments for revision.</p>
        </div>
    </div>
    <form method="post" class="sa-report-review__form" id="reportReviewForm">
        <?= csrfField() ?>
        <input type="hidden" name="submission_id" value="<?= (int) $submissionId ?>">
        <label class="form-label" for="reviewRemarks">Comments for the nurse</label>
        <textarea name="remarks"
                  id="reviewRemarks"
                  class="form-control"
                  rows="3"
                  placeholder="Required when rejecting — e.g. Please correct the prenatal care totals in Section I."><?= e($submission['remarks'] ?? '') ?></textarea>
        <div class="form-text">If you reject this report, the nurse will see your comment and can edit and resubmit.</div>
        <div class="sa-report-review__actions">
            <button type="submit"
                    name="action"
                    value="validate"
                    class="btn btn-success"
                    onclick="return confirm('Mark this report as validated?')">
                <i class="bi bi-check-circle me-1"></i>Validate report
            </button>
            <button type="submit"
                    name="action"
                    value="reject"
                    class="btn btn-danger"
                    id="rejectReportBtn">
                <i class="bi bi-x-circle me-1"></i>Reject &amp; return for revision
            </button>
        </div>
    </form>
</section>
<script>
document.getElementById('reportReviewForm').addEventListener('submit', function (e) {
    var submitter = e.submitter;
    if (!submitter || submitter.value !== 'reject') {
        return;
    }
    var remarks = document.getElementById('reviewRemarks').value.trim();
    if (remarks === '') {
        e.preventDefault();
        alert('Please add a comment explaining why this report is being rejected.');
        document.getElementById('reviewRemarks').focus();
        return;
    }
    if (!confirm('Reject this report and send it back to the nurse for revision?')) {
        e.preventDefault();
    }
});
</script>
<?php endif; ?>

<?php if ($submission['remarks'] && $submission['status'] !== 'submitted'): ?>
<div class="encode-remarks-alert<?= $submission['status'] === 'rejected' ? ' encode-remarks-alert--rejected' : '' ?>">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
        <strong><?= $submission['status'] === 'rejected' ? 'Returned for revision' : 'Remarks' ?></strong><br>
        <?= e($submission['remarks']) ?>
    </div>
</div>
<?php endif; ?>

<div class="encode-progress-card">
    <div class="encode-progress-ring" aria-hidden="true">
        <svg viewBox="0 0 64 64">
            <circle class="encode-progress-ring__bg" cx="32" cy="32" r="26"></circle>
            <circle class="encode-progress-ring__fill" id="progressRingFill" cx="32" cy="32" r="26"></circle>
        </svg>
        <span class="encode-progress-ring__label" id="progressRingLabel">0%</span>
    </div>
    <div class="encode-progress-details">
        <div class="encode-progress-details__title">Overall completion</div>
        <div class="encode-progress-details__stats">
            <span id="progressSummary">0 / 0 fields filled</span>
            <span class="text-muted"> · </span>
            <span><?= $totalParts ?> section<?= $totalParts !== 1 ? 's' : '' ?> total</span>
            <span class="text-muted"> · </span>
            <span><strong id="recordedTotalInline"><?= e(formatRecordedTotal($grandRecordedTotal)) ?></strong> total recorded</span>
        </div>
        <div class="encode-progress-bar-wrap" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progressBarWrap">
            <div class="encode-progress-bar-fill" id="progressBarFill"></div>
        </div>
    </div>
</div>

<?php if ($totalParts > 1): ?>
<div class="encode-stepper-card">
    <div class="step-pills" id="partStepPills" role="tablist" aria-label="Report sections">
        <?php foreach ($partsList as $i => $p): $idx = $i + 1; ?>
        <button type="button"
                class="step-pill <?= $idx === 1 ? 'step-pill--active' : '' ?>"
                data-part="<?= $idx ?>"
                role="tab">
            <span class="step-pill-number"><?= $idx ?></span>
            <span class="step-pill-label"><?= e($p['name']) ?></span>
            <span class="step-pill-count part-badge-<?= $idx ?>">0/<?= $p['count'] ?></span>
        </button>
        <?php if ($idx < $totalParts): ?>
        <div class="step-connector" aria-hidden="true"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="encode-toolbar">
    <div class="encode-search-wrap">
        <i class="bi bi-search encode-search-icon"></i>
        <input type="text"
               id="indicatorSearch"
               class="form-control"
               placeholder="Search indicators by name or code…"
               autocomplete="off"
               aria-label="Search indicators">
        <button type="button" class="encode-search-clear d-none" id="searchClearBtn" aria-label="Clear search">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <span class="encode-toolbar-hint">
        <kbd>Ctrl</kbd>+<kbd>S</kbd> save open section
    </span>
    <div class="encode-field-counter">
        <i class="bi bi-ui-checks-grid"></i>
        <span id="progressText">0 / 0 fields</span>
    </div>
</div>

<div id="reportSummary">
    <?php renderIndicatorCompactSummary($grouped, $stored, $partRecordedTotals); ?>
</div>

<?php renderPartEditModals($grouped, $stored); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var submissionId = <?= $submissionId ?>;
    var summaryEl = document.getElementById('reportSummary');
    var progressRingFill = document.getElementById('progressRingFill');
    var progressRingLabel = document.getElementById('progressRingLabel');
    var progressBarFill = document.getElementById('progressBarFill');
    var progressBarWrap = document.getElementById('progressBarWrap');
    var progressSummary = document.getElementById('progressSummary');
    var progressText = document.getElementById('progressText');
    var searchInput = document.getElementById('indicatorSearch');
    var searchClearBtn = document.getElementById('searchClearBtn');
    var dirty = false;
    var saving = false;
    var RING_CIRCUMFERENCE = 163.36;

    function updateProgress() {
        var cells = summaryEl.querySelectorAll('.value-cell');
        var total = cells.length;
        if (total === 0) return;
        var filled = 0;
        cells.forEach(function (c) {
            if (c.textContent.trim() !== '\u2014' && c.textContent.trim() !== '') filled++;
        });
        var pct = Math.round(filled / total * 100);
        var label = filled + ' / ' + total + ' fields filled';

        if (progressRingFill) {
            progressRingFill.style.strokeDashoffset = RING_CIRCUMFERENCE - (RING_CIRCUMFERENCE * pct / 100);
        }
        if (progressRingLabel) progressRingLabel.textContent = pct + '%';
        if (progressBarFill) progressBarFill.style.width = pct + '%';
        if (progressBarWrap) {
            progressBarWrap.setAttribute('aria-valuenow', String(pct));
            progressBarWrap.setAttribute('aria-label', label);
        }
        if (progressSummary) progressSummary.textContent = label;
        if (progressText) progressText.textContent = label;
        updateRecordedTotal();
    }

    function parseCellNumber(text) {
        text = text.trim();
        if (text === '' || text === '\u2014') return 0;
        var n = parseFloat(text.replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }

    function formatRecordedTotal(n) {
        if (Math.floor(n) === n) {
            return n.toLocaleString();
        }
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateRecordedTotal() {
        var grand = 0;
        summaryEl.querySelectorAll('.part-summary-card').forEach(function (card) {
            var partSum = 0;
            card.querySelectorAll('.part-indicator-table tbody tr').forEach(function (row) {
                var cells = row.querySelectorAll('.value-cell');
                var hasMft = false;
                cells.forEach(function (c) {
                    var sex = c.dataset.sex || '';
                    if (sex === 'M' || sex === 'F') hasMft = true;
                });
                cells.forEach(function (c) {
                    var sex = c.dataset.sex || '';
                    var n = parseCellNumber(c.textContent);
                    if (n === 0) return;
                    if (hasMft) {
                        if (sex === 'T') return;
                        if (sex === 'M' || sex === 'F') partSum += n;
                    } else {
                        partSum += n;
                    }
                });
            });
            grand += partSum;
            var badge = card.querySelector('.part-value-total-badge');
            if (badge) {
                badge.textContent = formatRecordedTotal(partSum) + ' recorded';
            }
        });

        var grandEl = document.getElementById('recordedGrandTotal');
        var inlineEl = document.getElementById('recordedTotalInline');
        var formatted = formatRecordedTotal(grand);
        if (grandEl) grandEl.textContent = formatted;
        if (inlineEl) inlineEl.textContent = formatted;
    }
    updateProgress();

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.toLowerCase().trim();
            if (searchClearBtn) searchClearBtn.classList.toggle('d-none', q === '');
            summaryEl.querySelectorAll('.part-summary-card').forEach(function (card) {
                var visibleRows = 0;
                card.querySelectorAll('.part-indicator-table tbody tr').forEach(function (row) {
                    var match = !q || row.textContent.toLowerCase().indexOf(q) !== -1;
                    row.style.display = match ? '' : 'none';
                    if (match) visibleRows++;
                });
                card.querySelectorAll('.part-category-block').forEach(function (cat) {
                    var has = Array.from(cat.querySelectorAll('.part-indicator-table tbody tr')).some(function (r) {
                        return r.style.display !== 'none';
                    });
                    cat.style.display = has || !q ? '' : 'none';
                });
                card.style.display = visibleRows > 0 || !q ? '' : 'none';
            });
        });
    }

    if (searchClearBtn) {
        searchClearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    }

    document.querySelectorAll('#partStepPills .step-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var idx = parseInt(pill.dataset.part, 10);
            var card = summaryEl.querySelector('.part-summary-card:nth-child(' + idx + ')');
            document.querySelectorAll('#partStepPills .step-pill').forEach(function (p) {
                p.classList.remove('step-pill--active');
                p.setAttribute('aria-selected', 'false');
            });
            pill.classList.add('step-pill--active');
            pill.setAttribute('aria-selected', 'true');
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('.edit-part-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx = btn.dataset.partIndex;
            var modal = document.getElementById('partModal' + idx);
            if (modal) new bootstrap.Modal(modal).show();
        });
    });

    document.querySelectorAll('.save-part-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (saving) return;
            saving = true;
            var idx = btn.dataset.partIndex;
            var modal = document.getElementById('partModal' + idx);
            var saveStatus = document.getElementById('modalSaveStatus' + idx);
            var inputs = modal.querySelectorAll('.indicator-input');

            var values = {};
            inputs.forEach(function (inp) {
                var id = inp.dataset.indicatorId;
                var sex = inp.dataset.sex || '_';
                var age = inp.dataset.ageGroup || '_';
                if (!values[id]) values[id] = {};
                if (!values[id][sex]) values[id][sex] = {};
                values[id][sex][age] = inp.value;
            });

            saveStatus.className = 'alert alert-info mb-0 py-1 px-3 small d-flex align-items-center gap-1';
            saveStatus.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
            saveStatus.classList.remove('d-none');
            btn.disabled = true;

            var fd = new FormData();
            fd.append('csrf_token', window.MHO.csrfToken);
            fd.append('submission_id', submissionId);
            for (var indId in values) {
                for (var s in values[indId]) {
                    for (var a in values[indId][s]) {
                        fd.append('values[' + indId + '][' + s + '][' + a + ']', values[indId][s][a]);
                    }
                }
            }

            fetch(window.MHO.baseUrl + '/api/save-draft.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    saving = false;
                    btn.disabled = false;
                    if (data.success) {
                        inputs.forEach(function (inp) {
                            var id = inp.dataset.indicatorId;
                            var sex = inp.dataset.sex;
                            var age = inp.dataset.ageGroup;
                            var cell = summaryEl.querySelector(
                                '.value-cell[data-indicator-id="' + id + '"]' +
                                '[data-sex="' + sex + '"]' +
                                '[data-age-group="' + age + '"]'
                            );
                            if (cell) {
                                var v = inp.value.trim();
                                if (v === '' || v === '0') v = '\u2014';
                                cell.textContent = v;
                            }
                        });
                        updateProgress();
                        dirty = false;
                        saveStatus.className = 'alert alert-success mb-0 py-1 px-3 small';
                        saveStatus.innerHTML = '\u2714 Saved ' + data.saved + ' field(s)';
                        setTimeout(function () {
                            saveStatus.classList.add('d-none');
                            var bsModal = bootstrap.Modal.getInstance(modal);
                            if (bsModal) bsModal.hide();
                        }, 1200);
                        updatePartBadges();
                    } else {
                        saveStatus.className = 'alert alert-danger mb-0 py-1 px-3 small';
                        saveStatus.innerHTML = '\u2716 ' + data.message;
                    }
                })
                .catch(function () {
                    saving = false;
                    btn.disabled = false;
                    saveStatus.className = 'alert alert-danger mb-0 py-1 px-3 small';
                    saveStatus.innerHTML = '\u2716 Save failed. Check connection.';
                });
        });
    });

    function updatePartBadges() {
        summaryEl.querySelectorAll('.part-summary-card').forEach(function (card, i) {
            var cells = card.querySelectorAll('.value-cell');
            var total = cells.length;
            if (total === 0) return;
            var filled = 0;
            cells.forEach(function (c) {
                if (c.textContent.trim() !== '\u2014' && c.textContent.trim() !== '') filled++;
            });
            var badge = card.querySelector('.part-count-badge');
            if (badge) {
                badge.className = 'badge part-count-badge ms-1 ' + (filled === total ? 'bg-success' : 'bg-secondary');
                badge.textContent = filled + '/' + total;
            }
            var stepBadge = document.querySelector('.part-badge-' + (i + 1));
            if (stepBadge) stepBadge.textContent = filled + '/' + total;
        });
    }
    updatePartBadges();

    document.querySelectorAll('.part-edit-modal').forEach(function (modal) {
        modal.addEventListener('input', function () { dirty = true; });
    });

    function beforeUnloadHandler(e) {
        e.preventDefault();
        e.returnValue = '';
    }

    setInterval(function () {
        if (dirty && !saving) {
            window.addEventListener('beforeunload', beforeUnloadHandler);
        } else {
            window.removeEventListener('beforeunload', beforeUnloadHandler);
        }
    }, 1000);

    setInterval(function () {
        if (dirty && !saving) {
            var openModal = document.querySelector('.part-edit-modal.show');
            if (openModal) {
                var saveBtn = openModal.querySelector('.save-part-btn');
                if (saveBtn) saveBtn.click();
            }
        }
    }, 45000);

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            var openModal = document.querySelector('.part-edit-modal.show');
            if (openModal) {
                var saveBtn = openModal.querySelector('.save-part-btn');
                if (saveBtn) saveBtn.click();
            }
        }
    });
});
</script>

<?php
saPageClose();
require_once __DIR__ . '/../includes/footer.php';
