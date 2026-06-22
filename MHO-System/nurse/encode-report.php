<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['nurse']);

$db = getDB();
$submissionId = (int) ($_GET['id'] ?? 0);
$submission = getSubmission($db, $submissionId);

if (!$submission || !canViewSubmission($submission)) {
    setFlash('danger', 'Report not found or access denied.');
    redirect(roleUrl('nurse', 'submissions.php'));
}

$editable = canEditSubmission($submission);
$pageTitle = 'Encode Report — ' . $submission['program_name'];

$grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
$stored  = loadIndicatorValues($db, $submissionId);

$partsList = [];
foreach ($grouped as $part => $categories) {
    $count = 0;
    foreach ($categories as $inds) {
        $count += count($inds);
    }
    $partsList[] = ['name' => $part, 'count' => $count];
}
$totalParts = count($partsList);

$extraScripts = ['report-form.js'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ── Meta header ── -->
<div class="report-meta-card mb-4">
    <div class="d-flex flex-wrap align-items-center gap-3 gap-md-4">
        <div class="d-flex align-items-center gap-2">
            <div class="meta-icon-box"><i class="bi bi-geo-alt"></i></div>
            <div>
                <div class="meta-label">Barangay</div>
                <div class="meta-value"><?= e($submission['barangay_name']) ?></div>
            </div>
        </div>
        <div class="meta-divider d-none d-md-block"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="meta-icon-box"><i class="bi bi-calendar"></i></div>
            <div>
                <div class="meta-label">Period</div>
                <div class="meta-value"><?= e(periodLabel((int)$submission['year'], (int)$submission['month'])) ?></div>
            </div>
        </div>
        <div class="meta-divider d-none d-md-block"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="meta-icon-box"><i class="bi bi-clipboard-pulse"></i></div>
            <div>
                <div class="meta-label">Program</div>
                <div class="meta-value"><?= e($submission['program_name']) ?></div>
            </div>
        </div>
        <div class="ms-md-auto d-flex align-items-center gap-2">
            <?= statusBadge($submission['status']) ?>
        </div>
    </div>
    <?php if ($submission['remarks'] && $submission['status'] === 'rejected'): ?>
    <div class="remarks-callout mt-3 px-3 py-2 rounded-2 small">
        <i class="bi bi-exclamation-triangle me-1" style="color:#d49a00;"></i>
        <strong>Rejection remarks:</strong> <?= e($submission['remarks']) ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($editable): ?>
<form id="reportForm" data-submission-id="<?= $submissionId ?>">

    <!-- ── Stepper ── -->
    <div class="step-pills" id="stepPills">
        <?php foreach ($partsList as $i => $p): $idx = $i + 1; ?>
        <button type="button" class="step-pill <?= $idx === 1 ? 'step-pill--active' : '' ?>"
                data-part="<?= $idx ?>">
            <span class="step-pill-number"><?= $idx ?></span>
            <span class="step-pill-label"><?= e($p['name']) ?></span>
            <span class="step-pill-count">
                <span class="step-filled-<?= $idx ?>">0</span>/<span class="step-total-<?= $idx ?>"><?= $p['count'] ?></span>
            </span>
        </button>
        <?php if ($idx < $totalParts): ?>
        <div class="step-connector"></div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- ── Search + progress ── -->
    <div class="d-flex align-items-center gap-3 mb-3">
        <div class="input-group input-group-sm flex-grow-1" style="max-width:340px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="indicatorSearch" class="form-control" placeholder="Search indicators...">
            <button class="btn btn-outline-secondary d-none" type="button" id="searchClearBtn">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="flex-shrink-0 small text-muted d-flex align-items-center gap-2" id="progressText">
            <i class="bi bi-check2-square"></i>
            <span id="progressSummary">0 / 0 fields</span>
        </div>
    </div>

    <?php renderIndicatorForm($grouped, $stored, false); ?>

    <!-- ── Sticky actions ── -->
    <div class="sticky-actions d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="prevPartBtn" class="btn btn-outline-secondary btn-nav" disabled>
                <i class="bi bi-chevron-left"></i> Previous
            </button>
            <button type="button" id="nextPartBtn" class="btn btn-primary btn-nav">
                Next <i class="bi bi-chevron-right"></i>
            </button>
            <span class="small text-muted ms-2 d-none d-md-inline" id="partIndicator">
                Part <span id="currentPartNum">1</span> of <?= $totalParts ?>
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="saveStatus" class="alert d-none mb-0 py-1 px-2 small"></div>
            <button type="button" id="copyPrevBtn" class="btn btn-outline-info btn-sm" title="Copy values from previous month">
                <i class="bi bi-copy"></i><span class="d-none d-sm-inline ms-1">Copy Previous</span>
            </button>
            <button type="button" id="saveDraftBtn" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-save"></i><span class="d-none d-sm-inline ms-1">Save Draft</span>
            </button>
            <button type="button" id="submitReportBtn" class="btn btn-success btn-sm">
                <i class="bi bi-send"></i><span class="d-none d-sm-inline ms-1">Submit</span>
            </button>
        </div>
    </div>

</form>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-body">
        <?php renderIndicatorForm($grouped, $stored, true); ?>
    </div>
</div>
<div class="mt-3">
    <a href="<?= e(roleUrl('nurse', 'submissions.php')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
