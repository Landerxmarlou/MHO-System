<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Report Targets';

$barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
$periods = $db->query('SELECT * FROM report_period ORDER BY year DESC, month DESC')->fetchAll();
$programs = $db->query('SELECT id, code, name FROM health_program ORDER BY id')->fetchAll();

$selectedBarangay = (int) ($_GET['barangay'] ?? ($barangays[0]['id'] ?? 0));
$selectedPeriod   = (int) ($_GET['period'] ?? ($periods[0]['id'] ?? 0));
$selectedProgram  = (int) ($_GET['program'] ?? ($programs[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $barangayId = (int) ($_POST['barangay_id'] ?? 0);
    $periodId   = (int) ($_POST['period_id'] ?? 0);
    $programId  = (int) ($_POST['program_id'] ?? 0);
    $targets    = $_POST['targets'] ?? [];

    $upsert = $db->prepare(
        'INSERT INTO report_target (barangay_id, period_id, indicator_id, target_value)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE target_value = VALUES(target_value)'
    );

    foreach ($targets as $indicatorId => $val) {
        if ($val !== '') {
            $upsert->execute([$barangayId, $periodId, (int)$indicatorId, (float)$val]);
        }
    }
    setFlash('success', 'Targets saved.');
    redirect(roleUrl('admin', "targets.php?barangay=$barangayId&period=$periodId&program=$programId"));
}

$indicators = [];
$existingTargets = [];
if ($selectedBarangay && $selectedPeriod && $selectedProgram) {
    $stmt = $db->prepare(
        'SELECT id, code, description FROM indicator WHERE program_id = ? AND is_active = 1 ORDER BY sort_order'
    );
    $stmt->execute([$selectedProgram]);
    $indicators = $stmt->fetchAll();

    $stmt = $db->prepare(
        'SELECT indicator_id, target_value FROM report_target
         WHERE barangay_id = ? AND period_id = ? AND indicator_id IN
         (SELECT id FROM indicator WHERE program_id = ?)'
    );
    $stmt->execute([$selectedBarangay, $selectedPeriod, $selectedProgram]);
    foreach ($stmt->fetchAll() as $t) {
        $existingTargets[$t['indicator_id']] = $t['target_value'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($barangays as $b): ?>
            <option value="<?= (int)$b['id'] ?>" <?= $selectedBarangay == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($periods as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $selectedPeriod == $p['id'] ? 'selected' : '' ?>>
                <?= e(periodLabel((int)$p['year'], (int)$p['month'])) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="program" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php foreach ($programs as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $selectedProgram == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (!empty($indicators)): ?>
<form method="post">
    <?= csrfField() ?>
    <input type="hidden" name="barangay_id" value="<?= $selectedBarangay ?>">
    <input type="hidden" name="period_id" value="<?= $selectedPeriod ?>">
    <input type="hidden" name="program_id" value="<?= $selectedProgram ?>">
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Set Targets</span>
            <button type="submit" class="btn btn-sm btn-primary">Save Targets</button>
        </div>
        <div class="table-responsive" style="max-height:65vh; overflow-y:auto;">
            <table class="table table-sm mb-0">
                <thead class="table-light sticky-top">
                    <tr><th>Code</th><th>Indicator</th><th style="width:120px;">Target</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($indicators as $ind): ?>
                    <tr>
                        <td><code class="small"><?= e($ind['code']) ?></code></td>
                        <td class="small"><?= e($ind['description']) ?></td>
                        <td>
                            <input type="number" step="0.01" min="0" name="targets[<?= (int)$ind['id'] ?>]"
                                   class="form-control form-control-sm"
                                   value="<?= e($existingTargets[$ind['id']] ?? '') ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
<?php else: ?>
<div class="alert alert-info">Select barangay, period, and program to set targets.</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
