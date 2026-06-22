<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['doctor']);

$db = getDB();
$pageTitle = 'Doctor Dashboard';

$submitted = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status IN ('submitted','validated')")->fetchColumn();
$validated = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'validated'")->fetchColumn();
$withRemarks = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE remarks IS NOT NULL AND remarks != ''")->fetchColumn();

$recent = $db->query(
    "SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.updated_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     WHERE rs.status IN ('submitted','validated')
     ORDER BY rs.updated_at DESC LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary"><?= $submitted ?></div>
                <div class="small text-muted">Available Reports</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= $validated ?></div>
                <div class="small text-muted">Validated</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-info"><?= $withRemarks ?></div>
                <div class="small text-muted">With Remarks</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Recent Reports</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Barangay</th><th>Program</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($recent)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No reports available.</td></tr>
                <?php else: foreach ($recent as $r): ?>
                <tr>
                    <td><?= e($r['barangay']) ?></td>
                    <td><?= e($r['program']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td>
                        <a href="<?= e(roleUrl('doctor', 'view-report.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
