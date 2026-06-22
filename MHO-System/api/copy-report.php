<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/indicators.php';
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

// Find previous period for same barangay + program
$stmt = $db->prepare("
    SELECT id FROM report_period
    WHERE (year < ? OR (year = ? AND month < ?))
    ORDER BY year DESC, month DESC LIMIT 1
");
$stmt->execute([$submission['year'], $submission['year'], $submission['month']]);
$prevPeriod = $stmt->fetch();

if (!$prevPeriod) {
    jsonResponse(['success' => false, 'message' => 'No previous period found.'], 404);
}

// Find submission from previous period for same barangay + program
$stmt = $db->prepare("
    SELECT id FROM report_submission
    WHERE barangay_id = ? AND program_id = ? AND period_id = ?
    LIMIT 1
");
$stmt->execute([$submission['barangay_id'], $submission['program_id'], $prevPeriod['id']]);
$prevSubmission = $stmt->fetch();

if (!$prevSubmission) {
    jsonResponse(['success' => false, 'message' => 'No report found for the previous period.'], 404);
}

// Copy indicator values
$stmt = $db->prepare("
    SELECT indicator_id, sex, age_group, value
    FROM indicator_value
    WHERE submission_id = ?
");
$stmt->execute([$prevSubmission['id']]);
$prevValues = $stmt->fetchAll();

if (empty($prevValues)) {
    jsonResponse(['success' => false, 'message' => 'Previous report has no indicator values to copy.'], 404);
}

// Upsert into current submission
$upsert = $db->prepare("
    INSERT INTO indicator_value (submission_id, indicator_id, sex, age_group, value)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE value = VALUES(value)
");

$count = 0;
foreach ($prevValues as $row) {
    $upsert->execute([$submissionId, (int)$row['indicator_id'], $row['sex'], $row['age_group'], (float)$row['value']]);
    $count++;
}

$db->prepare('UPDATE report_submission SET updated_at = NOW() WHERE id = ?')->execute([$submissionId]);
logAudit($db, 'indicator_value', $submissionId, 'COPY', null, ['copied_from' => $prevSubmission['id'], 'fields' => $count]);

jsonResponse(['success' => true, 'message' => "Copied $count field(s) from previous period.", 'copied' => $count]);
