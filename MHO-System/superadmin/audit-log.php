<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Audit Log';

$search  = trim($_GET['search'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

$todayEntries = (int) $db->query("SELECT COUNT(*) FROM audit_log WHERE DATE(changed_at) = CURDATE()")->fetchColumn();
$totalUsers = (int) $db->query("SELECT COUNT(DISTINCT changed_by) FROM audit_log WHERE changed_by IS NOT NULL")->fetchColumn();
$firstEntry = $db->query("SELECT MIN(changed_at) FROM audit_log")->fetchColumn();

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = '(al.table_name LIKE ? OR al.action LIKE ? OR al.new_value LIKE ? OR al.old_value LIKE ? OR u.full_name LIKE ?)';
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($dateFrom !== '') {
    $where[] = 'al.changed_at >= ?';
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo !== '') {
    $where[] = 'al.changed_at <= ?';
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$stmtCount = $db->prepare("SELECT COUNT(DISTINCT al.id) FROM audit_log al LEFT JOIN users u ON u.id = al.changed_by $whereClause");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();
$stmt = $db->prepare(
    "SELECT al.*, u.full_name AS changed_by_name, u.role AS changed_by_role,
            GROUP_CONCAT(DISTINCT b.name ORDER BY b.name SEPARATOR ', ') AS user_brgys
     FROM audit_log al
     LEFT JOIN users u ON u.id = al.changed_by
     LEFT JOIN user_barangay ub ON ub.user_id = u.id
     LEFT JOIN barangay b ON b.id = ub.barangay_id
     $whereClause
     GROUP BY al.id
     ORDER BY al.changed_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$totalPages = max(1, (int) ceil($total / $perPage));

$actionColors = [
    'INSERT' => 'success',
    'UPDATE' => 'info',
    'DELETE' => 'danger',
];

$hasFilters = $search !== '' || $dateFrom !== '' || $dateTo !== '';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Entries</div>
                    <div class="fs-4 fw-bold"><?= number_format($total) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="text-muted small">Today</div>
                    <div class="fs-4 fw-bold"><?= $todayEntries ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Users</div>
                    <div class="fs-4 fw-bold"><?= $totalUsers ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="text-muted small">Since</div>
                    <div class="fs-6 fw-bold"><?= $firstEntry ? e(date('M j, Y', strtotime($firstEntry))) : '—' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="get" class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-medium text-muted mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="User, action, table, or details..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium text-muted mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium text-muted mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if ($hasFilters): ?>
                <a href="?" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-check me-2"></i>Audit Log</span>
        <span class="text-muted small"><?= number_format($total) ?> <?= $hasFilters ? 'matching ' : '' ?>entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr class="text-center">
                    <th>User</th><th>Action</th><th>Role</th><th>Brgy</th><th>Date/Time</th><th>Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">
                    <i class="bi bi-inbox d-block fs-2 mb-2 text-muted opacity-50"></i>
                    <?= $hasFilters ? 'No matching entries found.' : 'No audit entries yet.' ?>
                </td></tr>
                <?php else: foreach ($logs as $log): ?>
                <tr class="text-center">
                    <td class="small"><?= e($log['changed_by_name'] ?? 'System') ?></td>
                    <td class="small"><?= e($log['action']) ?> <code class="small"><?= e($log['table_name']) ?></code></td>
                    <td class="small"><?= e(ucfirst($log['changed_by_role'] ?? '')) ?: '—' ?></td>
                    <td class="small"><?= e($log['user_brgys'] ?? '—') ?></td>
                    <td class="small text-nowrap"><?= e(date('M j, g:i A', strtotime($log['changed_at']))) ?></td>
                    <td class="small" style="max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?php if ($log['new_value'] || $log['old_value']): ?>
                        <span title="<?= e($log['new_value'] ?? $log['old_value'] ?? '') ?>">
                            <?= e($log['new_value'] ?? $log['old_value'] ?? '') ?>
                        </span>
                        <?php else: ?>
                        <span class="fst-italic">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > 30): ?>
    <?php
    $queryParams = array_filter(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
    $queryString = http_build_query($queryParams);
    ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?><?= $queryString ? '&' : '' ?>page=<?= $page - 1 ?>">Prev</a>
                </li>
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="?<?= $queryString ?><?= $queryString ? '&' : '' ?>page=1">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?><?= $queryString ? '&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?<?= $queryString ?><?= $queryString ? '&' : '' ?>page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $queryString ?><?= $queryString ? '&' : '' ?>page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>