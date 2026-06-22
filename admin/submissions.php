<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'All Submissions';

$where = '1=1';
$params = [];

if (!empty($_GET['status'])) {
    $where .= ' AND rs.status = ?';
    $params[] = $_GET['status'];
}
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
     ORDER BY rp.year DESC, rp.month DESC, b.name"
);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
$programs = $db->query('SELECT id, name FROM health_program ORDER BY id')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <?php foreach (['draft','submitted','validated','rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="barangay" class="form-select form-select-sm">
            <option value="">All Barangays</option>
            <?php foreach ($barangays as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= ($_GET['barangay'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="program" class="form-select form-select-sm">
            <option value="">All Programs</option>
            <?php foreach ($programs as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ($_GET['program'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
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
                <tr><td colspan="6" class="text-center text-muted py-4">No submissions found.</td></tr>
                <?php else: foreach ($submissions as $s): ?>
                <tr>
                    <td><?= e(periodLabel((int)$s['year'], (int)$s['month'])) ?></td>
                    <td><?= e($s['barangay']) ?></td>
                    <td class="small"><?= e($s['program']) ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td class="small"><?= e($s['submitted_by_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($s['status'] === 'submitted'): ?>
                        <a href="<?= e(roleUrl('admin', 'validate.php?id=' . $s['id'])) ?>" class="btn btn-sm btn-warning">Validate</a>
                        <?php else: ?>
                        <a href="<?= e(roleUrl('admin', 'validate.php?id=' . $s['id'])) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
