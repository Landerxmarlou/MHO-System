<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['superadmin']);

$db = getDB();
$pageTitle = 'Report Periods';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $year  = (int) ($_POST['year'] ?? 0);
    $month = (int) ($_POST['month'] ?? 0);
    if ($year >= 2020 && $month >= 1 && $month <= 12) {
        try {
            $db->prepare('INSERT INTO report_period (year, month) VALUES (?, ?)')->execute([$year, $month]);
            setFlash('success', 'Period added: ' . periodLabel($year, $month));
        } catch (PDOException $e) {
            setFlash('danger', 'Period already exists.');
        }
    }
    redirect(roleUrl('superadmin', 'periods.php'));
}

$periods = $db->query('SELECT * FROM report_period ORDER BY year DESC, month DESC')->fetchAll();
require_once __DIR__ . '/../includes/header.php';

saPageOpen();
saBreadcrumb([
    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-house-door'],
    ['label' => 'Periods'],
]);
saHeader('Report Periods', 'Manage monthly reporting periods for health submissions');

saPanelOpen('All Periods', [
    'count' => count($periods),
    'actions' => '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodModal"><i class="bi bi-plus-lg me-1"></i>Add Period</button>',
]);
?>
<div class="table-responsive">
    <table class="table mb-0">
        <thead><tr><th>Label</th><th>Year</th><th>Month</th></tr></thead>
            <tbody>
                <?php foreach ($periods as $p): ?>
                <tr>
                    <td><?= e(periodLabel((int)$p['year'], (int)$p['month'])) ?></td>
                    <td><?= (int)$p['year'] ?></td>
                    <td><?= monthName((int)$p['month']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>
<?php saPanelClose(); saPageClose(); ?>

<div class="modal fade" id="addPeriodModal" tabindex="-1" aria-labelledby="addPeriodModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPeriodModalLabel">Add Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" min="2020" max="2099" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == (int)date('n') ? 'selected' : '' ?>><?= monthName($m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
