<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['doctor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    setFlash('danger', 'Invalid request.');
    redirect(roleUrl('doctor', 'submissions.php'));
}

$submissionId = (int) ($_POST['submission_id'] ?? 0);
$remarks = trim($_POST['remarks'] ?? '');

$db = getDB();
$submission = getSubmission($db, $submissionId);

if (!$submission || !canViewSubmission($submission)) {
    setFlash('danger', 'Report not found.');
    redirect(roleUrl('doctor', 'submissions.php'));
}

$oldRemarks = $submission['remarks'];
$db->prepare('UPDATE report_submission SET remarks = ?, updated_at = NOW() WHERE id = ?')
   ->execute([$remarks ?: null, $submissionId]);

logAudit($db, 'report_submission', $submissionId, 'UPDATE',
    ['remarks' => $oldRemarks],
    ['remarks' => $remarks]
);

setFlash('success', 'Clinical remarks saved.');
redirect(roleUrl('doctor', 'view-report.php?id=' . $submissionId));
