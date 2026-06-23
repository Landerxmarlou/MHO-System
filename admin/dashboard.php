<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Admin Dashboard';

$pending = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'submitted'")->fetchColumn();
$validated = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status IN ('validated', 'archived')")->fetchColumn();
$rejected = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'rejected'")->fetchColumn();
$total = (int) $db->query('SELECT COUNT(*) FROM report_submission')->fetchColumn();

$recent = $db->query(
    "SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.submitted_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     WHERE rs.status = 'submitted'
     ORDER BY rs.submitted_at DESC LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-warning"><?= $pending ?></div>
                <div class="small text-muted">Pending Validation</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= $validated ?></div>
                <div class="small text-muted">In Archive</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-danger"><?= $rejected ?></div>
                <div class="small text-muted">Rejected</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary"><?= $total ?></div>
                <div class="small text-muted">Total Submissions</div>
            </div>
        </div>
    </div>
</div>

<?php if ($validated > 0): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Report Archive</span>
        <a href="<?= e(roleUrl('admin', 'archive.php')) ?>" class="btn btn-sm btn-success">View Archive</a>
    </div>
    <div class="card-body py-3">
        <span class="badge bg-success"><?= $validated ?> validated report<?= $validated !== 1 ? 's' : '' ?></span>
        stored as read-only archive records.
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Pending Validation</span>
        <a href="<?= e(roleUrl('admin', 'submissions.php')) ?>" class="btn btn-sm btn-primary">Review All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Barangay</th><th>Program</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($recent)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No pending submissions.</td></tr>
                <?php else: foreach ($recent as $r): ?>
                <tr>
                    <td><?= e($r['barangay']) ?></td>
                    <td><?= e($r['program']) ?></td>
                    <td class="small text-muted"><?= e($r['submitted_at'] ?? '—') ?></td>
                    <td>
                        <a href="<?= e(roleUrl('admin', 'submissions.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-primary">Review</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
