<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
require_once __DIR__ . '/../includes/export.php';
requireRole(['admin']);

$db = getDB();
$type = $_GET['type'] ?? 'summary';
$submissionId = (int) ($_GET['id'] ?? 0);

$query = [
    'barangay' => $_GET['barangay'] ?? '',
    'program'  => $_GET['program'] ?? '',
];

if ($type === 'detail') {
    if ($submissionId <= 0) {
        setFlash('danger', 'Select a report to export.');
        redirect(roleUrl('admin', 'archive.php'));
    }

    $submission = getSubmission($db, $submissionId);
    if (!$submission || !in_array($submission['status'], ['validated', 'archived'], true)) {
        setFlash('danger', 'Only validated archive reports can be exported.');
        redirect(roleUrl('admin', 'archive.php'));
    }

    $rows = fetchArchiveDetailRows($db, $submissionId);
    if (empty($rows)) {
        setFlash('warning', 'This program has no indicators configured to export.');
        redirect(roleUrl('admin', 'archive.php?id=' . $submissionId));
    }

    sendCsvDownload(
        exportArchiveFilename('mho-archive-report', $submission),
        archiveDetailHeaders(),
        $rows
    );
}

if ($type === 'full') {
    $rows = fetchArchiveFullRows($db, $query);
    if (empty($rows)) {
        setFlash('warning', 'No archive data matches your filters.');
        redirect(roleUrl('admin', 'archive.php'));
    }

    sendCsvDownload(
        exportArchiveFilename('mho-archive-full'),
        archiveDetailHeaders(),
        $rows
    );
}

$rows = fetchArchiveSummaryRows($db, $query);
if (empty($rows)) {
    setFlash('warning', 'No archive reports match your filters.');
    redirect(roleUrl('admin', 'archive.php'));
}

sendCsvDownload(
    exportArchiveFilename('mho-archive-summary'),
    archiveSummaryHeaders(),
    $rows
);
