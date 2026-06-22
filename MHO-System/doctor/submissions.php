<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['doctor']);

$db = getDB();
$pageTitle = 'Submissions';

$where = "rs.status IN ('submitted','validated','rejected')";
$params = [];

if (!empty($_GET['status'])) {
    $where = 'rs.status = ?';
    $params[] = $_GET['status'];
}

$stmt = $db->prepare(
    "SELECT rs.id, b.name AS barangay, hp.name AS program,
            rp.year, rp.month, rs.status, rs.remarks, rs.updated_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     JOIN report_period rp ON rp.id = rs.period_id
     WHERE $where
     ORDER BY rp.year DESC, rp.month DESC, b.name"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="btn-group btn-group-sm mb-3">
    <a href="?" class="btn btn-outline-secondary <?= empty($_GET['status']) ? 'active' : '' ?>">All</a>
    <?php foreach (['submitted','validated','rejected'] as $s): ?>
    <a href="?status=<?= $s ?>" class="btn btn-outline-secondary <?= ($_GET['status'] ?? '') === $s ? 'active' : '' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th><th>Barangay</th><th>Program</th><th>Status</th><th>Remarks</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No submissions found.</td></tr>
                <?php else: foreach ($submissions as $s): ?>
                <tr>
                    <td><?= e(periodLabel((int)$s['year'], (int)$s['month'])) ?></td>
                    <td><?= e($s['barangay']) ?></td>
                    <td class="small"><?= e($s['program']) ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td class="small text-muted"><?= e($s['remarks'] ? substr($s['remarks'], 0, 40) . '...' : '—') ?></td>
                    <td>
                        <a href="<?= e(roleUrl('doctor', 'view-report.php?id=' . $s['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
