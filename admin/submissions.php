<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Pending Submissions';
$submissionId = (int) ($_GET['id'] ?? 0);

function adminSubmissionsListUrl(array $query = []): string
{
    $params = array_filter([
        'barangay' => $query['barangay'] ?? ($_GET['barangay'] ?? ''),
        'program'  => $query['program'] ?? ($_GET['program'] ?? ''),
    ], static fn($v) => $v !== '' && $v !== null);

    $qs = http_build_query($params);
    return roleUrl('admin', 'submissions.php' . ($qs ? '?' . $qs : ''));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int) ($_POST['submission_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    $submission = getSubmission($db, $id);
    if (!$submission) {
        setFlash('danger', 'Submission not found.');
        redirect(adminSubmissionsListUrl());
    }

    if ($action === 'validate' && $submission['status'] === 'submitted') {
        $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
        $stored = loadIndicatorValues($db, $id);
        $totalParticipants = sumRecordedIndicatorValues($grouped, $stored)['grand'];

        $db->prepare(
            "UPDATE report_submission
             SET status='validated', validated_by=?, validated_at=NOW(), remarks=?,
                 total_participants=?, updated_at=NOW()
             WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks ?: null, $totalParticipants, $id]);
        logAudit($db, 'report_submission', $id, 'UPDATE',
            ['status' => 'submitted'],
            ['status' => 'validated', 'total_participants' => $totalParticipants]
        );
        setFlash('success', 'Report validated and saved to the read-only archive.');
        redirect(roleUrl('admin', 'archive.php?id=' . $id));
    } elseif ($action === 'reject' && $submission['status'] === 'submitted') {
        if ($remarks === '') {
            setFlash('danger', 'Remarks are required when rejecting a report.');
            redirect(roleUrl('admin', 'submissions.php?id=' . $id));
        }
        $db->prepare(
            "UPDATE report_submission SET status='rejected', validated_by=?, validated_at=NOW(), remarks=?, updated_at=NOW() WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks, $id]);
        logAudit($db, 'report_submission', $id, 'UPDATE', ['status' => 'submitted'], ['status' => 'rejected']);
        setFlash('success', 'Report rejected. Nurse can revise and resubmit.');
        redirect(adminSubmissionsListUrl());
    }

    redirect(roleUrl('admin', 'submissions.php?id=' . $id));
}

$submission = null;
$grouped = [];
$stored = [];
$submittedBy = null;
$validatedBy = null;
$grandRecordedTotal = 0;

if ($submissionId > 0) {
    $submission = getSubmission($db, $submissionId);
    if (!$submission) {
        setFlash('danger', 'Submission not found.');
        redirect(adminSubmissionsListUrl());
    }

    $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
    $stored = loadIndicatorValues($db, $submissionId);
    $grandRecordedTotal = sumRecordedIndicatorValues($grouped, $stored)['grand'];
    $pageTitle = 'Review — ' . $submission['program_name'];

    if (!empty($submission['submitted_by'])) {
        $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['submitted_by']]);
        $submittedBy = $stmt->fetch();
    }
    if (!empty($submission['validated_by'])) {
        $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['validated_by']]);
        $validatedBy = $stmt->fetch();
    }
}

$pendingCount = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'submitted'")->fetchColumn();

$submissions = [];
$barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
$programs = $db->query('SELECT id, name FROM health_program ORDER BY id')->fetchAll();

if ($submissionId <= 0) {
    $where = "rs.status = 'submitted'";
    $params = [];

    if (!empty($_GET['barangay'])) {
        $where .= ' AND rs.barangay_id = ?';
        $params[] = (int) $_GET['barangay'];
    }
    if (!empty($_GET['program'])) {
        $where .= ' AND rs.program_id = ?';
        $params[] = (int) $_GET['program'];
    }

    $stmt = $db->prepare(
        "SELECT rs.id, b.name AS barangay, hp.name AS program,
                rp.year, rp.month, rs.status, rs.submitted_at, u.full_name AS submitted_by_name
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         LEFT JOIN users u ON u.id = rs.submitted_by
         WHERE $where
         ORDER BY rs.submitted_at ASC, rp.year DESC, rp.month DESC, b.name"
    );
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($submission): ?>

<div class="mb-3">
    <a href="<?= e(adminSubmissionsListUrl()) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Submissions
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><strong>Barangay:</strong> <?= e($submission['barangay_name']) ?></div>
            <div class="col-md-3"><strong>Period:</strong> <?= e(periodLabel((int) $submission['year'], (int) $submission['month'])) ?></div>
            <div class="col-md-3"><strong>Program:</strong> <?= e($submission['program_name']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <?= statusBadge($submission['status']) ?></div>
            <?php if (!empty($submission['report_code'])): ?>
            <div class="col-md-3"><strong>Report Code:</strong> <code><?= e($submission['report_code']) ?></code></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($submission['remarks'] && $submission['status'] === 'rejected'): ?>
<div class="alert alert-warning">
    <strong>Returned for revision</strong><br>
    <?= e($submission['remarks']) ?>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Report Summary</div>
    <div class="card-body p-0 report-summary-body">
        <?php renderIndicatorForm($grouped, $stored, true); ?>
    </div>
</div>

<?php if ($submission['status'] === 'submitted'): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Validation Action</div>
    <div class="card-body">
        <form method="post" id="validationForm">
            <?= csrfField() ?>
            <input type="hidden" name="submission_id" value="<?= $submissionId ?>">
            <div class="mb-3">
                <label class="form-label" for="validationRemarks">Remarks (required for rejection)</label>
                <textarea name="remarks" id="validationRemarks" class="form-control" rows="3"><?= e($submission['remarks'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="validate" class="btn btn-success"
                        data-confirm="Validate this report?">
                    <i class="bi bi-check-circle me-1"></i>Confirm
                </button>
                <button type="submit" name="action" value="reject" class="btn btn-danger" id="rejectReportBtn">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('validationForm').addEventListener('submit', function (e) {
    var submitter = e.submitter;
    if (!submitter || submitter.value !== 'reject') {
        return;
    }
    var remarks = document.getElementById('validationRemarks').value.trim();
    if (remarks === '') {
        e.preventDefault();
        alert('Remarks are required when rejecting a report.');
        document.getElementById('validationRemarks').focus();
    }
});
</script>

<?php elseif (in_array($submission['status'], ['validated', 'archived'], true)): ?>
<div class="alert alert-info mb-0">
    This report is <?= e($submission['status']) ?>.
    <a href="<?= e(roleUrl('admin', 'archive.php?id=' . $submissionId)) ?>" class="alert-link">Open in Report Archive</a>
</div>

<?php elseif ($submission['status'] === 'draft'): ?>
<div class="alert alert-secondary mb-0">
    This report is still a draft and has not been submitted for validation.
</div>
<?php endif; ?>

<?php else: ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted small mb-0">
        <?php if ($pendingCount > 0): ?>
        <span class="badge bg-warning text-dark"><?= $pendingCount ?> pending validation</span>
        <?php else: ?>
        No reports pending validation.
        <?php endif; ?>
    </p>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="barangay" class="form-select form-select-sm">
            <option value="">All Barangays</option>
            <?php foreach ($barangays as $b): ?>
            <option value="<?= (int) $b['id'] ?>" <?= ($_GET['barangay'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="program" class="form-select form-select-sm">
            <option value="">All Programs</option>
            <?php foreach ($programs as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= ($_GET['program'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="?" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th><th>Barangay</th><th>Program</th><th>Status</th>
                    <th>Submitted By</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No reports pending validation.</td></tr>
                <?php else: foreach ($submissions as $s): ?>
                <tr>
                    <td><?= e(periodLabel((int) $s['year'], (int) $s['month'])) ?></td>
                    <td><?= e($s['barangay']) ?></td>
                    <td class="small"><?= e($s['program']) ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td class="small"><?= e($s['submitted_by_name'] ?? '—') ?></td>
                    <td>
                        <a href="<?= e(roleUrl('admin', 'submissions.php?id=' . $s['id'])) ?>" class="btn btn-sm btn-warning">Validate</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
