<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Consolidated Reports';

$summary = $db->query(
    'SELECT * FROM vw_submission_summary ORDER BY year DESC, month DESC, barangay, program'
)->fetchAll();

$byStatus = $db->query(
    'SELECT status, COUNT(*) AS cnt FROM report_submission GROUP BY status'
)->fetchAll(PDO::FETCH_KEY_PAIR);

$byProgram = $db->query(
    'SELECT hp.name, COUNT(rs.id) AS cnt
     FROM health_program hp
     LEFT JOIN report_submission rs ON rs.program_id = hp.id
     GROUP BY hp.id ORDER BY hp.id'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-4">
    <?php foreach ($byStatus as $status => $cnt): ?>
    <div class="col-auto">
        <span class="badge bg-light text-dark border p-2">
            <?= statusBadge($status) ?> <strong><?= (int)$cnt ?></strong>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Submissions by Program</div>
            <div class="card-body">
                <?php foreach ($byProgram as $row): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="small"><?= e($row['name']) ?></span>
                    <span class="badge bg-primary"><?= (int)$row['cnt'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Submission Summary</div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th><th>Barangay</th><th>Program</th><th>Status</th>
                    <th>Indicators</th><th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($summary)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No data yet.</td></tr>
                <?php else: foreach ($summary as $s): ?>
                <tr>
                    <td><?= e(periodLabel((int)$s['year'], (int)$s['month'])) ?></td>
                    <td><?= e($s['barangay']) ?></td>
                    <td class="small"><?= e($s['program']) ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td><?= (int)$s['indicator_count'] ?></td>
                    <td class="small text-muted"><?= e($s['submitted_at'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
