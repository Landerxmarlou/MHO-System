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
    jsonResponse(['success' => false, 'message' => 'You cannot edit this submission.'], 403);
}

require_once __DIR__ . '/../includes/indicators.php';

$values = $_POST['values'] ?? [];
$saved = saveIndicatorValues($db, $submissionId, $values);

if (($_SESSION['role'] ?? '') === 'nurse') {
    $db->prepare(
        'UPDATE report_submission SET updated_at = NOW(), submitted_by = COALESCE(submitted_by, ?) WHERE id = ?'
    )->execute([(int) $_SESSION['user_id'], $submissionId]);
} else {
    $db->prepare('UPDATE report_submission SET updated_at = NOW() WHERE id = ?')->execute([$submissionId]);
}
logAudit($db, 'indicator_value', $submissionId, 'UPDATE', null, ['fields_saved' => $saved]);

jsonResponse(['success' => true, 'message' => "Saved $saved field(s).", 'saved' => $saved]);
