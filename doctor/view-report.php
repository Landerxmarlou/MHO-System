<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['doctor']);

$db = getDB();
$submissionId = (int) ($_GET['id'] ?? 0);
$submission = getSubmission($db, $submissionId);

if (!$submission || !canViewSubmission($submission)) {
    setFlash('danger', 'Report not found.');
    redirect(roleUrl('doctor', 'submissions.php'));
}

$pageTitle = 'View Report — ' . $submission['program_name'];
$grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
$stored  = loadIndicatorValues($db, $submissionId);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-3">
    <a href="<?= e(roleUrl('doctor', 'submissions.php')) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><strong>Barangay:</strong> <?= e($submission['barangay_name']) ?></div>
            <div class="col-md-3"><strong>Period:</strong> <?= e(periodLabel((int)$submission['year'], (int)$submission['month'])) ?></div>
            <div class="col-md-3"><strong>Program:</strong> <?= e($submission['program_name']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <?= statusBadge($submission['status']) ?></div>
        </div>
        <?php if ($submission['remarks']): ?>
        <div class="alert alert-info mt-2 mb-0 py-2 small">
            <strong>Remarks:</strong> <?= e($submission['remarks']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Report Summary</div>
    <div class="card-body p-0 report-summary-body">
        <?php renderIndicatorForm($grouped, $stored, true); ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Clinical Remarks</div>
    <div class="card-body">
        <form method="post" action="<?= e(roleUrl('doctor', 'add-remarks.php')) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="submission_id" value="<?= $submissionId ?>">
            <textarea name="remarks" class="form-control mb-3" rows="4"
                      placeholder="Add clinical observations or recommendations..."><?= e($submission['remarks'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-chat-left-text me-1"></i>Save Remarks
            </button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
