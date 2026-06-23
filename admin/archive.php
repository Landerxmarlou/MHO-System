<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
requireRole(['admin']);

$db = getDB();
$pageTitle = 'Report Archive';
$submissionId = (int) ($_GET['id'] ?? 0);

function adminArchiveListUrl(array $query = []): string
{
    $params = array_filter([
        'barangay' => $query['barangay'] ?? ($_GET['barangay'] ?? ''),
        'program'  => $query['program'] ?? ($_GET['program'] ?? ''),
    ], static fn($v) => $v !== '' && $v !== null);

    $qs = http_build_query($params);
    return roleUrl('admin', 'archive.php' . ($qs ? '?' . $qs : ''));
}

function adminArchiveExportUrl(string $type, ?int $id = null): string
{
    $params = array_filter([
        'type'     => $type,
        'id'       => $id,
        'barangay' => $_GET['barangay'] ?? '',
        'program'  => $_GET['program'] ?? '',
    ], static fn($v) => $v !== '' && $v !== null);

    return roleUrl('admin', 'export-archive.php?' . http_build_query($params));
}

$submission = null;
$grouped = [];
$stored = [];
$submittedBy = null;
$validatedBy = null;
$archivedBy = null;
$grandRecordedTotal = 0;

if ($submissionId > 0) {
    $submission = getSubmission($db, $submissionId);
    if (!$submission || !in_array($submission['status'], ['validated', 'archived'], true)) {
        setFlash('danger', 'Only validated reports are stored in the archive.');
        redirect(adminArchiveListUrl());
    }

    $grouped = loadIndicatorsGrouped($db, (int) $submission['program_id']);
    $stored = loadIndicatorValues($db, $submissionId);

    if (isset($submission['total_participants']) && $submission['total_participants'] !== null) {
        $grandRecordedTotal = (float) $submission['total_participants'];
    } else {
        $grandRecordedTotal = sumRecordedIndicatorValues($grouped, $stored)['grand'];
    }

    $pageTitle = 'Archived Report — ' . $submission['program_name'];

    if (!empty($submission['submitted_by'])) {
        $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['submitted_by']]);
        $submittedBy = $stmt->fetch();
    }
    if (!empty($submission['validated_by'])) {
        $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['validated_by']]);
        $validatedBy = $stmt->fetch();
    }
    if (!empty($submission['archived_by'])) {
        $stmt = $db->prepare('SELECT full_name, username, position FROM users WHERE id = ?');
        $stmt->execute([(int) $submission['archived_by']]);
        $archivedBy = $stmt->fetch();
    }
}

$archiveCount = (int) $db->query("SELECT COUNT(*) FROM report_submission WHERE status IN ('validated', 'archived')")->fetchColumn();

$where = "rs.status IN ('validated', 'archived')";
$params = [];

if (!empty($_GET['barangay'])) {
    $where .= ' AND rs.barangay_id = ?';
    $params[] = (int) $_GET['barangay'];
}
if (!empty($_GET['program'])) {
    $where .= ' AND rs.program_id = ?';
    $params[] = (int) $_GET['program'];
}

if ($submissionId <= 0) {
    $stmt = $db->prepare(
        "SELECT rs.id, b.name AS barangay, hp.name AS program,
                rp.year, rp.month, rs.status, rs.submitted_at, rs.validated_at,
                rs.report_code, u.full_name AS submitted_by_name, rs.total_participants
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         LEFT JOIN users u ON u.id = rs.submitted_by
         WHERE $where
         ORDER BY COALESCE(rs.validated_at, rs.submitted_at) DESC, rp.year DESC, rp.month DESC"
    );
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} else {
    $reports = [];
}

$barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
$programs = $db->query('SELECT id, name FROM health_program ORDER BY id')->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($submission): ?>

<div class="mb-3 d-flex flex-wrap align-items-center gap-2">
    <a href="<?= e(adminArchiveListUrl()) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Archive
    </a>
    <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Read-only</span>
    <a href="<?= e(adminArchiveExportUrl('detail', $submissionId)) ?>" class="btn btn-sm btn-success ms-auto">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export to Excel
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><strong>Barangay:</strong> <?= e($submission['barangay_name']) ?></div>
            <div class="col-md-3"><strong>Period:</strong> <?= e(periodLabel((int) $submission['year'], (int) $submission['month'])) ?></div>
            <div class="col-md-3"><strong>Program:</strong> <?= e($submission['program_name']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <?= statusBadge($submission['status']) ?></div>
            <?php if (!empty($submission['report_code'])): ?>
            <div class="col-md-3"><strong>Report Code:</strong> <code><?= e($submission['report_code']) ?></code></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-archive me-1"></i>Archive Record
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Submitted By</dt>
            <dd class="col-sm-9"><?= $submittedBy ? e($submittedBy['full_name']) : '—' ?></dd>

            <dt class="col-sm-3">Submitted At</dt>
            <dd class="col-sm-9"><?= e($submission['submitted_at'] ?? '—') ?></dd>

            <dt class="col-sm-3">Validated By</dt>
            <dd class="col-sm-9"><?= $validatedBy ? e($validatedBy['full_name']) : '—' ?></dd>

            <dt class="col-sm-3">Validated At</dt>
            <dd class="col-sm-9"><?= e($submission['validated_at'] ?? '—') ?></dd>

            <dt class="col-sm-3">Total Participants</dt>
            <dd class="col-sm-9"><strong><?= e(formatRecordedTotal($grandRecordedTotal)) ?></strong></dd>

            <?php if (!empty($submission['remarks'])): ?>
            <dt class="col-sm-3">Validation Remarks</dt>
            <dd class="col-sm-9"><?= e($submission['remarks']) ?></dd>
            <?php endif; ?>

            <?php if ($submission['status'] === 'archived' && $archivedBy): ?>
            <dt class="col-sm-3">Archived By</dt>
            <dd class="col-sm-9"><?= e($archivedBy['full_name']) ?></dd>
            <dt class="col-sm-3">Archived At</dt>
            <dd class="col-sm-9"><?= e($submission['archived_at'] ?? '—') ?></dd>
            <?php endif; ?>

            <?php if (!empty($submission['archive_notes'])): ?>
            <dt class="col-sm-3">Archive Notes</dt>
            <dd class="col-sm-9"><?= e($submission['archive_notes']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Report Summary</div>
    <div class="card-body p-0 report-summary-body">
        <?php renderIndicatorForm($grouped, $stored, true); ?>
    </div>
</div>

<?php else: ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <p class="text-muted small mb-0">
        <span class="badge bg-success"><?= $archiveCount ?> validated report<?= $archiveCount !== 1 ? 's' : '' ?></span>
        stored as read-only archive records.
    </p>
    <?php if ($archiveCount > 0): ?>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(adminArchiveExportUrl('summary')) ?>" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Summary
        </a>
        <a href="<?= e(adminArchiveExportUrl('full')) ?>" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>Export Full Data
        </a>
    </div>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="barangay" class="form-select form-select-sm">
            <option value="">All Barangays</option>
            <?php foreach ($barangays as $b): ?>
            <option value="<?= (int) $b['id'] ?>" <?= ($_GET['barangay'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="program" class="form-select form-select-sm">
            <option value="">All Programs</option>
            <?php foreach ($programs as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= ($_GET['program'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
        <a href="<?= e(adminArchiveListUrl(['barangay' => '', 'program' => ''])) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
        Validated Reports (<?= count($reports) ?>)
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Period</th>
                    <th>Barangay</th>
                    <th>Program</th>
                    <th>Status</th>
                    <th>Submitted By</th>
                    <th>Validated At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No validated reports yet. Reports appear here automatically after admin validation.
                    </td>
                </tr>
                <?php else: foreach ($reports as $r): ?>
                <tr>
                    <td><?= e(periodLabel((int) $r['year'], (int) $r['month'])) ?></td>
                    <td><?= e($r['barangay']) ?></td>
                    <td class="small"><?= e($r['program']) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td class="small"><?= e($r['submitted_by_name'] ?? '—') ?></td>
                    <td class="small text-muted"><?= e($r['validated_at'] ?? '—') ?></td>
                    <td>
                        <a href="<?= e(roleUrl('admin', 'archive.php?id=' . $r['id'])) ?>" class="btn btn-sm btn-outline-dark">View</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
