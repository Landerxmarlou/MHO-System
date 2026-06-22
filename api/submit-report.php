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

$reportCode = trim((string) ($submission['report_code'] ?? ''));
if ($reportCode === '') {
    try {
        $reportCode = generateReportCode($db, $submissionId);
    } catch (InvalidArgumentException $e) {
        jsonResponse(['success' => false, 'message' => 'Could not generate report code.'], 400);
    }
}

try {
    $stmt = $db->prepare(
        "UPDATE report_submission
         SET status = 'submitted', submitted_by = ?, submitted_at = NOW(),
             report_code = ?, updated_at = NOW()
         WHERE id = ?"
    );
    $stmt->execute([$_SESSION['user_id'], $reportCode, $submissionId]);
} catch (PDOException $e) {
    jsonResponse(['success' => false, 'message' => 'Report code conflict. Please contact an administrator.'], 409);
}

logAudit($db, 'report_submission', $submissionId, 'UPDATE',
    ['status' => $submission['status']],
    ['status' => 'submitted', 'report_code' => $reportCode]
);

setFlash('success', 'Report submitted successfully. Reference code: ' . $reportCode);

jsonResponse([
    'success'     => true,
    'message'     => 'Report submitted successfully.',
    'report_code' => $reportCode,
]);
