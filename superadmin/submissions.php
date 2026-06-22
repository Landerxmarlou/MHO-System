<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Nurse Submissions';

$allowedStatuses = ['submitted', 'validated', 'rejected'];
$where = "rs.status IN ('submitted', 'validated', 'rejected')";
$params = [];

if (!empty($_GET['status']) && in_array($_GET['status'], $allowedStatuses, true)) {
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
    "SELECT rs.id, rs.report_code, b.name AS barangay, hp.name AS program,
            rp.year, rp.month, rs.status, rs.submitted_at, rs.updated_at,
            u.full_name AS submitted_by_name, u.username AS submitted_by_username
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

saPageOpen();
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Submissions'],
]);
saHeader('Nurse Submissions', 'View and manage all monthly health report submissions');
?>

<div class="sa-filter-pills">
    <a href="?" class="btn btn-outline-secondary btn-sm <?= empty($_GET['status']) ? 'active' : '' ?>">All</a>
    <?php foreach ($allowedStatuses as $s): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['status' => $s])) ?>"
       class="btn btn-outline-secondary btn-sm <?= ($_GET['status'] ?? '') === $s ? 'active' : '' ?>">
        <?= ucfirst($s) ?>
    </a>
    <?php endforeach; ?>
</div>

<?php saToolbarOpen(); ?>
<form method="get" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
    <?php if (!empty($_GET['status'])): ?>
    <input type="hidden" name="status" value="<?= e($_GET['status']) ?>">
    <?php endif; ?>
    <select name="barangay" class="form-select" style="max-width:200px;">
        <option value="">All Barangays</option>
        <?php foreach ($barangays as $b): ?>
        <option value="<?= (int) $b['id'] ?>" <?= ($_GET['barangay'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="program" class="form-select" style="max-width:240px;">
        <option value="">All Programs</option>
        <?php foreach ($programs as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= ($_GET['program'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="?" class="btn btn-outline-secondary">Reset</a>
</form>
<?php saToolbarClose(); ?>

<?php saPanelOpen('All Nurse Submissions', ['count' => count($submissions)]); ?>
<div class="table-responsive">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Report Code</th><th>Period</th><th>Barangay</th><th>Program</th><th>Status</th>
                <th>Condition</th><th>Submitted By</th><th>Updated</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($submissions)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No submissions found.</td></tr>
            <?php else: foreach ($submissions as $s):
                $conditionAt = $s['submitted_at'] ?: $s['updated_at'];
            ?>
            <tr>
                <td>
                    <?php if (!empty($s['report_code'])): ?>
                    <code class="small"><?= e($s['report_code']) ?></code>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= e(periodLabel((int) $s['year'], (int) $s['month'])) ?></td>
                <td><?= e($s['barangay']) ?></td>
                <td class="small"><?= e($s['program']) ?></td>
                <td><?= statusBadge($s['status']) ?></td>
                <td><?= submissionConditionBadge($conditionAt) ?></td>
                <td class="small">
                    <?php if ($s['submitted_by_name']): ?>
                    <div><?= e($s['submitted_by_name']) ?></div>
                    <div class="text-muted"><?= e($s['submitted_by_username']) ?></div>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted"><?= e($s['updated_at']) ?></td>
                <td>
                    <a href="<?= e(roleUrl('superadmin', 'view-report.php?id=' . $s['id'])) ?>"
                       class="btn btn-sm btn-outline-primary">View</a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php saPanelClose(); saPageClose(); ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
