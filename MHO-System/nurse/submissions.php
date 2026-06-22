<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['nurse']);

$db = getDB();
$pageTitle = 'My Reports';

$barangayIds = getAssignedBarangayIds();
$submissions = [];

if (!empty($barangayIds)) {
    $placeholders = implode(',', array_fill(0, count($barangayIds), '?'));
    $params = $barangayIds;

    $where = "rs.barangay_id IN ($placeholders)";
    if (!empty($_GET['status'])) {
        $where .= ' AND rs.status = ?';
        $params[] = $_GET['status'];
    }

    $stmt = $db->prepare(
        "SELECT rs.id, b.name AS barangay, hp.name AS program, hp.code AS program_code,
                rp.year, rp.month, rs.status, rs.submitted_at, rs.updated_at
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         WHERE $where
         ORDER BY rp.year DESC, rp.month DESC, b.name"
    );
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="btn-group btn-group-sm">
        <a href="?" class="btn btn-outline-secondary <?= empty($_GET['status']) ? 'active' : '' ?>">All</a>
        <?php foreach (['draft','submitted','validated','rejected'] as $s): ?>
        <a href="?status=<?= $s ?>" class="btn btn-outline-secondary <?= ($_GET['status'] ?? '') === $s ? 'active' : '' ?>">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <a href="<?= e(roleUrl('nurse', 'new-report.php')) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>New Report
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th><th>Barangay</th><th>Program</th><th>Status</th><th>Updated</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No reports found.</td></tr>
                <?php else: foreach ($submissions as $s): ?>
                <tr>
                    <td><?= e(periodLabel((int)$s['year'], (int)$s['month'])) ?></td>
                    <td><?= e($s['barangay']) ?></td>
                    <td><?= e($s['program']) ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td class="small text-muted"><?= e($s['updated_at']) ?></td>
                    <td>
                        <a href="<?= e(roleUrl('nurse', 'encode-report.php?id=' . $s['id'])) ?>"
                           class="btn btn-sm btn-outline-primary">
                            <?= in_array($s['status'], ['draft','rejected']) ? 'Encode' : 'View' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
