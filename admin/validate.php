<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Validate Reports';

$submissionId = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int) ($_POST['submission_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    $submission = getSubmission($db, $id);
    if (!$submission) {
        setFlash('danger', 'Submission not found.');
        redirect(roleUrl('admin', 'validate.php'));
    }

    if ($action === 'validate' && $submission['status'] === 'submitted') {
        $db->prepare(
            "UPDATE report_submission SET status='validated', validated_by=?, validated_at=NOW(), remarks=?, updated_at=NOW() WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks ?: null, $id]);
        logAudit($db, 'report_submission', $id, 'UPDATE', ['status' => 'submitted'], ['status' => 'validated']);
        setFlash('success', 'Report validated successfully.');
    } elseif ($action === 'reject' && $submission['status'] === 'submitted') {
        if ($remarks === '') {
            setFlash('danger', 'Remarks are required when rejecting a report.');
            redirect(roleUrl('admin', 'validate.php?id=' . $id));
        }
        $db->prepare(
            "UPDATE report_submission SET status='rejected', validated_by=?, validated_at=NOW(), remarks=?, updated_at=NOW() WHERE id=?"
        )->execute([$_SESSION['user_id'], $remarks, $id]);
        logAudit($db, 'report_submission', $id, 'UPDATE', ['status' => 'submitted'], ['status' => 'rejected']);
        setFlash('success', 'Report rejected. Nurse can revise and resubmit.');
    }
    redirect(roleUrl('admin', 'validate.php'));
}

if ($submissionId > 0) {
    $submission = getSubmission($db, $submissionId);
    if (!$submission) {
        setFlash('danger', 'Submission not found.');
        redirect(roleUrl('admin', 'validate.php'));
    }
    $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
    $stored  = loadIndicatorValues($db, $submissionId);
    $pageTitle = 'Review — ' . $submission['program_name'];
}

$pending = $db->query(
    "SELECT rs.id, b.name AS barangay, hp.name AS program, rp.year, rp.month, rs.submitted_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     JOIN report_period rp ON rp.id = rs.period_id
     WHERE rs.status = 'submitted'
     ORDER BY rs.submitted_at ASC"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($submissionId > 0 && isset($submission)): ?>
<div class="mb-3">
    <a href="<?= e(roleUrl('admin', 'validate.php')) ?>" class="btn btn-sm btn-outline-secondary">
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
    </div>
</div>

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
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="submission_id" value="<?= $submissionId ?>">
            <div class="mb-3">
                <label class="form-label">Remarks (required for rejection)</label>
                <textarea name="remarks" class="form-control" rows="3"><?= e($submission['remarks'] ?? '') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="validate" class="btn btn-success"
                        data-confirm="Validate this report?">
                    <i class="bi bi-check-circle me-1"></i>Validate
                </button>
                <button type="submit" name="action" value="reject" class="btn btn-danger">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Pending Validation (<?= count($pending) ?>)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Period</th><th>Barangay</th><th>Program</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($pending)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No pending reports.</td></tr>
                <?php else: foreach ($pending as $p): ?>
                <tr>
                    <td><?= e(periodLabel((int)$p['year'], (int)$p['month'])) ?></td>
                    <td><?= e($p['barangay']) ?></td>
                    <td><?= e($p['program']) ?></td>
                    <td class="small"><?= e($p['submitted_at']) ?></td>
                    <td>
                        <a href="?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Review</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
