<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['nurse']);

$db = getDB();
$pageTitle = 'Nurse Dashboard';

$barangayIds = getAssignedBarangayIds();
$placeholders = implode(',', array_fill(0, max(1, count($barangayIds)), '?'));

$year = (int) date('Y');
$month = (int) date('n');

$programs = $db->query('SELECT id, code, name FROM health_program ORDER BY id')->fetchAll();

$statusCounts = ['draft' => 0, 'submitted' => 0, 'validated' => 0, 'rejected' => 0];
if (!empty($barangayIds)) {
    $stmt = $db->prepare(
        "SELECT rs.status, COUNT(*) AS cnt
         FROM report_submission rs
         JOIN report_period rp ON rp.id = rs.period_id
         WHERE rs.barangay_id IN ($placeholders) AND rp.year = ? AND rp.month = ?
         GROUP BY rs.status"
    );
    $stmt->execute(array_merge($barangayIds, [$year, $month]));
    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = (int) $row['cnt'];
    }
}

$recent = [];
if (!empty($barangayIds)) {
    $stmt = $db->prepare(
        "SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.updated_at
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         WHERE rs.barangay_id IN ($placeholders)
         ORDER BY rs.updated_at DESC LIMIT 8"
    );
    $stmt->execute($barangayIds);
    $recent = $stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>
<?php if (empty($barangayIds)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    No barangays assigned to your account. Contact the administrator.
</div>
<?php else: ?>
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Draft', 'count' => $statusCounts['draft'], 'color' => 'secondary'],
        ['label' => 'Submitted', 'count' => $statusCounts['submitted'], 'color' => 'warning'],
        ['label' => 'Validated', 'count' => $statusCounts['validated'], 'color' => 'success'],
        ['label' => 'Rejected', 'count' => $statusCounts['rejected'], 'color' => 'danger'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-<?= $c['color'] ?>"><?= $c['count'] ?></div>
                <div class="small text-muted"><?= $c['label'] ?> (<?= monthName($month) ?>)</div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Health Programs</div>
            <div class="list-group list-group-flush">
                <?php foreach ($programs as $p): ?>
                <a href="<?= e(roleUrl('nurse', 'new-report.php?program=' . $p['id'])) ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between">
                    <span><?= e($p['name']) ?></span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Recent Reports</div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light"><tr><th>Barangay</th><th>Program</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($recent)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No reports yet.</td></tr>
                        <?php else: foreach ($recent as $r): ?>
                        <tr>
                            <td><?= e($r['barangay']) ?></td>
                            <td class="small"><?= e($r['program']) ?></td>
                            <td><?= statusBadge($r['status']) ?></td>
                            <td>
                                <a href="<?= e(roleUrl('nurse', 'encode-report.php?id=' . $r['id'])) ?>"
                                   class="btn btn-sm btn-outline-primary py-0">Open</a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
