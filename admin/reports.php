<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Consolidated Reports';

$summary = $db->query(
    'SELECT * FROM vw_submission_summary ORDER BY year DESC, month DESC, barangay, program'
)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>
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
