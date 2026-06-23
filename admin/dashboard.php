<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Dashboard';

$pending = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'submitted'")->fetchColumn();
$validated = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status IN ('validated', 'archived')")->fetchColumn();
$rejected = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'rejected'")->fetchColumn();
$total = (int) $db->query('SELECT COUNT(*) FROM report_submission')->fetchColumn();
$thisMonth = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'submitted' AND DATE_FORMAT(submitted_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')")->fetchColumn();
$drafts = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'draft'")->fetchColumn();

$statusRows = $db->query('SELECT status, COUNT(*) AS cnt FROM report_submission GROUP BY status')->fetchAll();
$statusLabels = [];
$statusData = [];
$statusColors = [];
$colorMap = [
    'draft'     => '#6c757d',
    'submitted' => '#1a7a60',
    'validated' => '#198754',
    'archived'  => '#0d6efd',
    'rejected'  => '#dc3545',
];
foreach ($statusRows as $r) {
    $statusLabels[] = ucfirst($r['status']);
    $statusData[] = (int) $r['cnt'];
    $statusColors[] = $colorMap[$r['status']] ?? '#adb5bd';
}

$monthlyRows = $db->query("
    SELECT DATE_FORMAT(COALESCE(submitted_at, updated_at), '%Y-%m') AS mo, COUNT(*) AS cnt
    FROM report_submission
    WHERE COALESCE(submitted_at, updated_at) >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
      AND status != 'draft'
    GROUP BY mo ORDER BY mo
")->fetchAll();
$monthLabels = [];
$monthData = [];
foreach ($monthlyRows as $r) {
    $monthLabels[] = $r['mo'];
    $monthData[] = (int) $r['cnt'];
}

$recentValidations = $db->query("
    SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.validated_at, u.full_name AS validated_by_name
    FROM report_submission rs
    JOIN barangay b ON b.id = rs.barangay_id
    JOIN health_program hp ON hp.id = rs.program_id
    LEFT JOIN users u ON u.id = rs.validated_by
    WHERE rs.status IN ('validated', 'archived', 'rejected')
      AND rs.validated_at IS NOT NULL
    ORDER BY rs.validated_at DESC
    LIMIT 5
")->fetchAll();

$recent = $db->query(
    "SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.submitted_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     WHERE rs.status = 'submitted'
     ORDER BY rs.submitted_at DESC LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/superadmin-ui.php';

saPageOpen('admin-page');
saHeader('Dashboard', 'Overview of pending validations, archive records, and submission activity');
?>

<div class="encode-meta-grid encode-meta-grid--stats mb-4">
    <?php
    $statItems = [
        ['icon' => 'bi-clock-history', 'label' => 'Pending Validation', 'value' => (string) $pending],
        ['icon' => 'bi-archive-fill', 'label' => 'In Archive', 'value' => (string) $validated],
        ['icon' => 'bi-x-circle-fill', 'label' => 'Rejected', 'value' => (string) $rejected],
        ['icon' => 'bi-file-earmark-text-fill', 'label' => 'Total Submissions', 'value' => (string) $total],
        ['icon' => 'bi-calendar-check-fill', 'label' => 'Submitted This Month', 'value' => (string) $thisMonth],
        ['icon' => 'bi-pencil-square', 'label' => 'Drafts', 'value' => (string) $drafts],
    ];
    foreach ($statItems as $item):
    ?>
    <div class="encode-meta-item">
        <div class="encode-meta-item__icon"><i class="bi <?= e($item['icon']) ?>"></i></div>
        <div>
            <div class="encode-meta-item__label"><?= e($item['label']) ?></div>
            <div class="encode-meta-item__value fs-stat"><?= e($item['value']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-chart-panel">
            <div class="sa-chart-panel__header">Submission Status</div>
            <div class="sa-chart-panel__body d-flex justify-content-center">
                <canvas id="statusChart" width="240" height="240" style="max-height:240px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sa-chart-panel">
            <div class="sa-chart-panel__header">Submissions (Last 6 Months)</div>
            <div class="sa-chart-panel__body">
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="sa-panel">
            <div class="sa-panel__header">
                <span class="sa-panel__title"><i class="bi bi-activity me-1"></i>Recent Validations</span>
                <div class="sa-panel__actions">
                    <a href="<?= e(roleUrl('admin', 'archive.php')) ?>" class="btn btn-sm btn-outline-primary">View Archive</a>
                </div>
            </div>
            <div class="sa-panel__body sa-activity-list">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentValidations)): ?>
                    <div class="list-group-item text-center text-muted py-3">No validation activity yet.</div>
                    <?php else: foreach ($recentValidations as $v): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="small">
                            <span class="fw-medium"><?= e($v['barangay']) ?></span>
                            <span class="text-muted">·</span>
                            <span class="text-muted"><?= e($v['program']) ?></span>
                            <?= statusBadge($v['status']) ?>
                            <?php if (!empty($v['validated_by_name'])): ?>
                            <span class="text-muted">by <?= e($v['validated_by_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted text-nowrap ms-2"><?= e($v['validated_at']) ?></small>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sa-panel">
            <div class="sa-panel__header">
                <span class="sa-panel__title"><i class="bi bi-lightning-fill me-1"></i>Quick Actions</span>
            </div>
            <div class="sa-panel__body padded">
                <div class="sa-quick-actions">
                    <a href="<?= e(roleUrl('admin', 'submissions.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-inbox me-1"></i>Submissions</a>
                    <a href="<?= e(roleUrl('admin', 'archive.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-archive me-1"></i>Archive</a>
                    <a href="<?= e(roleUrl('admin', 'targets.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-bullseye me-1"></i>Targets</a>
                    <a href="<?= e(roleUrl('admin', 'reports.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-bar-chart me-1"></i>Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
saPanelOpen('Pending Validation', [
    'count' => count($recent),
    'actions' => '<a href="' . e(roleUrl('admin', 'submissions.php')) . '" class="btn btn-sm btn-outline-primary">Review All</a>',
]);
?>
<div class="table-responsive">
    <table class="table mb-0">
        <thead>
            <tr><th>Barangay</th><th>Program</th><th>Submitted</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($recent)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">No reports pending validation.</td></tr>
            <?php else: foreach ($recent as $r): ?>
            <tr>
                <td><?= e($r['barangay']) ?></td>
                <td><?= e($r['program']) ?></td>
                <td class="small text-muted"><?= e($r['submitted_at'] ?? '—') ?></td>
                <td>
                    <a href="<?= e(roleUrl('admin', 'submissions.php?id=' . $r['id'])) ?>"
                       class="btn btn-sm btn-outline-primary py-0">Review</a>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php saPanelClose(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{ data: <?= json_encode($statusData) ?>, backgroundColor: <?= json_encode($statusColors) ?>, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
    });
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{ label: 'Submissions', data: <?= json_encode($monthData) ?>, backgroundColor: '#1a7a60', borderRadius: 6 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});
</script>

<?php
saPageClose();
require_once __DIR__ . '/../includes/footer.php';
