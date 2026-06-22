<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

if (!verifyCsrf()) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

$submissionId = (int) ($_POST['submission_id'] ?? 0);
if ($submissionId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid submission.'], 400);
}

$db = getDB();
$submission = getSubmission($db, $submissionId);

if (!$submission || !canEditSubmission($submission)) {
    jsonResponse(['success' => false, 'message' => 'You cannot submit this report.'], 403);
}

if (!in_array($submission['status'], ['draft', 'rejected'], true)) {
    jsonResponse(['success' => false, 'message' => 'Only draft or rejected reports can be submitted.'], 400);
}

$stmt = $db->prepare(
    "UPDATE report_submission
     SET status = 'submitted', submitted_by = ?, submitted_at = NOW(), updated_at = NOW()
     WHERE id = ?"
);
$stmt->execute([$_SESSION['user_id'], $submissionId]);

logAudit($db, 'report_submission', $submissionId, 'UPDATE',
    ['status' => $submission['status']],
    ['status' => 'submitted']
);

jsonResponse(['success' => true, 'message' => 'Report submitted successfully.']);
