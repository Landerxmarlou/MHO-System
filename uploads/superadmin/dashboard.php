<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Dashboard';

$stats = [
    'users'       => (int) $db->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn(),
    'barangays'   => (int) $db->query('SELECT COUNT(*) FROM barangay WHERE is_active = 1')->fetchColumn(),
    'programs'    => (int) $db->query('SELECT COUNT(*) FROM health_program')->fetchColumn(),
    'submissions' => (int) $db->query('SELECT COUNT(*) FROM report_submission')->fetchColumn(),
];

$pendingVal = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status = 'submitted'")->fetchColumn();
$thisMonth  = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE DATE_FORMAT(updated_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')")->fetchColumn();

$statusRows = $db->query('SELECT status, COUNT(*) AS cnt FROM report_submission GROUP BY status')->fetchAll();
$statusLabels = [];
$statusData = [];
$statusColors = [];
$colorMap = ['draft' => '#6c757d', 'submitted' => '#1a7a60', 'validated' => '#198754', 'rejected' => '#dc3545'];
foreach ($statusRows as $r) {
    $statusLabels[] = ucfirst($r['status']);
    $statusData[] = (int) $r['cnt'];
    $statusColors[] = $colorMap[$r['status']] ?? '#adb5bd';
}

$monthlyRows = $db->query("
    SELECT DATE_FORMAT(updated_at,'%Y-%m') AS mo, COUNT(*) AS cnt
    FROM report_submission
    WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
    GROUP BY mo ORDER BY mo
")->fetchAll();
$monthLabels = [];
$monthData = [];
foreach ($monthlyRows as $r) {
    $monthLabels[] = $r['mo'];
    $monthData[] = (int) $r['cnt'];
}

$recentAudit = $db->query("
    SELECT al.*, u.full_name
    FROM audit_log al
    LEFT JOIN users u ON u.id = al.changed_by
    ORDER BY al.changed_at DESC LIMIT 5
")->fetchAll();

$recent = $db->query(
    'SELECT rs.id, b.name AS barangay, hp.name AS program, rs.status, rs.updated_at
     FROM report_submission rs
     JOIN barangay b ON b.id = rs.barangay_id
     JOIN health_program hp ON hp.id = rs.program_id
     ORDER BY rs.updated_at DESC LIMIT 10'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saHeader('Dashboard', 'Overview of records, submissions, and system activity');
?>

<div class="encode-meta-grid encode-meta-grid--stats mb-4">
    <?php
    $statItems = [
        ['icon' => 'bi-people-fill', 'label' => 'Active Users', 'value' => (string) $stats['users']],
        ['icon' => 'bi-geo-alt-fill', 'label' => 'Barangays', 'value' => (string) $stats['barangays']],
        ['icon' => 'bi-journal-medical', 'label' => 'Programs', 'value' => (string) $stats['programs']],
        ['icon' => 'bi-file-earmark-text-fill', 'label' => 'Submissions', 'value' => (string) $stats['submissions']],
        ['icon' => 'bi-clock-history', 'label' => 'Pending Validation', 'value' => (string) $pendingVal],
        ['icon' => 'bi-calendar-check-fill', 'label' => 'Submitted This Month', 'value' => (string) $thisMonth],
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
                <span class="sa-panel__title"><i class="bi bi-activity me-1"></i>Recent Activity</span>
                <div class="sa-panel__actions">
                    <a href="<?= e(roleUrl('superadmin', 'audit-log.php')) ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
            <div class="sa-panel__body sa-activity-list">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentAudit)): ?>
                    <div class="list-group-item text-center text-muted py-3">No recent activity.</div>
                    <?php else: foreach ($recentAudit as $a): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="small">
                            <span class="fw-medium"><?= e($a['full_name'] ?? 'System') ?></span>
                            <span class="text-muted"><?= e($a['action']) ?></span>
                            <span class="text-muted">on</span>
                            <code class="fw-medium"><?= e($a['table_name']) ?></code>
                            <span class="text-muted">#<?= (int) $a['record_id'] ?></span>
                        </div>
                        <small class="text-muted text-nowrap ms-2"><?= e($a['changed_at']) ?></small>
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
                    <a href="<?= e(roleUrl('superadmin', 'users.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>Users</a>
                    <a href="<?= e(roleUrl('superadmin', 'barangays.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-geo-alt me-1"></i>Barangays</a>
                    <a href="<?= e(roleUrl('superadmin', 'programs.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-medical me-1"></i>Programs</a>
                    <a href="<?= e(roleUrl('superadmin', 'periods.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar me-1"></i>Periods</a>
                    <a href="<?= e(roleUrl('superadmin', 'submissions.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-text me-1"></i>Submissions</a>
                    <a href="<?= e(roleUrl('superadmin', 'audit-log.php')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-check me-1"></i>Audit Log</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
saPanelOpen('Recent Submissions', [
    'count' => count($recent),
    'actions' => '<a href="' . e(roleUrl('superadmin', 'submissions.php')) . '" class="btn btn-sm btn-outline-primary">View All</a>',
]);
?>
<div class="table-responsive">
    <table class="table mb-0">
        <thead>
            <tr><th>Barangay</th><th>Program</th><th>Status</th><th>Updated</th><th></th></tr>
        </thead>
        <tbody>
            <?php if (empty($recent)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No submissions yet.</td></tr>
            <?php else: foreach ($recent as $r): ?>
            <tr>
                <td><?= e($r['barangay']) ?></td>
                <td><?= e($r['program']) ?></td>
                <td><?= statusBadge($r['status']) ?></td>
                <td class="small text-muted"><?= e($r['updated_at']) ?></td>
                <td>
                    <a href="<?= e(roleUrl('superadmin', 'view-report.php?id=' . $r['id'])) ?>"
                       class="btn btn-sm btn-outline-primary py-0">View</a>
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
