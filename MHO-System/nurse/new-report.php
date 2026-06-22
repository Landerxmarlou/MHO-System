<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['nurse']);

$db = getDB();
$pageTitle = 'New Report';

$barangayIds = getAssignedBarangayIds();
if (empty($barangayIds)) {
    setFlash('danger', 'No barangays assigned. Contact administrator.');
    redirect(roleUrl('nurse', 'dashboard.php'));
}

$barangays = loadUserBarangays($db, (int) $_SESSION['user_id']);
$programs = $db->query('SELECT id, code, name FROM health_program ORDER BY id')->fetchAll();
$periods = $db->query('SELECT * FROM report_period ORDER BY year DESC, month DESC')->fetchAll();

$preselectProgram = (int) ($_GET['program'] ?? 0);
$showForce = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $barangayId = (int) ($_POST['barangay_id'] ?? 0);
    $periodId   = (int) ($_POST['period_id'] ?? 0);
    $programId  = (int) ($_POST['program_id'] ?? 0);

    if (!canAccessBarangay($barangayId) || $periodId <= 0 || $programId <= 0) {
        setFlash('danger', 'Invalid selection.');
    } else {
        $stmt = $db->prepare(
            'SELECT id, status FROM report_submission WHERE barangay_id = ? AND period_id = ? AND program_id = ?'
        );
        $stmt->execute([$barangayId, $periodId, $programId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if (!isset($_POST['force_create'])) {
                $showForce = true;
                setFlash('warning', 'A <strong>' . e($existing['status']) . '</strong> report already exists for this combination. Check "Create fresh draft" below and submit again to replace it.');
            } else {
                $db->prepare('DELETE FROM indicator_value WHERE submission_id = ?')->execute([$existing['id']]);
                $db->prepare('DELETE FROM report_submission WHERE id = ?')->execute([$existing['id']]);
                $stmt = $db->prepare(
                    'INSERT INTO report_submission (barangay_id, period_id, program_id, status) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$barangayId, $periodId, $programId, 'draft']);
                $newId = (int) $db->lastInsertId();
                logAudit($db, 'report_submission', $newId, 'INSERT', null, ['status' => 'draft']);
                setFlash('success', 'New report created. Previous draft was replaced.');
                redirect(roleUrl('nurse', 'encode-report.php?id=' . $newId));
            }
        } else {
            $stmt = $db->prepare(
                'INSERT INTO report_submission (barangay_id, period_id, program_id, status) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$barangayId, $periodId, $programId, 'draft']);
            $newId = (int) $db->lastInsertId();
            logAudit($db, 'report_submission', $newId, 'INSERT', null, ['status' => 'draft']);
            setFlash('success', 'New report created. Start encoding indicators.');
            redirect(roleUrl('nurse', 'encode-report.php?id=' . $newId));
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Create New Report</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Barangay</label>
                        <select name="barangay_id" class="form-select" required>
                            <?php foreach ($barangays as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= isset($barangayId) && (int)$b['id'] === $barangayId ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reporting Period</label>
                        <select name="period_id" class="form-select" required>
                            <?php foreach ($periods as $p): ?>
                            <option value="<?= (int)$p['id'] ?>" <?= isset($periodId) && (int)$p['id'] === $periodId ? 'selected' : '' ?>>
                                <?= e(periodLabel((int)$p['year'], (int)$p['month'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Health Program</label>
                        <select name="program_id" class="form-select" required>
                            <?php foreach ($programs as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                <?= (isset($programId) && (int)$p['id'] === $programId) || $preselectProgram === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= e($p['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($showForce): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="force_create" id="forceCreate" value="1">
                        <label class="form-check-label small text-danger" for="forceCreate">
                            <i class="bi bi-exclamation-triangle me-1"></i>Create fresh draft (existing will be replaced)
                        </label>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-plus me-1"></i>Create Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
