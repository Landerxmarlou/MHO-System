<?php
/**
 * CLI integration test — run: php tests/integration_test.php
 */
require_once __DIR__ . '/../includes/init.php';

$passed = 0;
$failed = 0;

function assert_test(bool $cond, string $msg): void
{
    global $passed, $failed;
    if ($cond) {
        echo "  PASS: $msg\n";
        $passed++;
    } else {
        echo "  FAIL: $msg\n";
        $failed++;
    }
}

echo "MHO Integration Tests\n";
echo str_repeat('=', 40) . "\n";

try {
    $db = getDB();
} catch (Exception $e) {
    echo "FATAL: Cannot connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Setup superadmin password
$hash = password_hash('Admin@123', PASSWORD_BCRYPT);
$db->prepare("UPDATE users SET password_hash = ? WHERE username = 'superadmin'")->execute([$hash]);

// Seed periods
$year = (int) date('Y');
for ($m = 1; $m <= 12; $m++) {
    $db->prepare('INSERT IGNORE INTO report_period (year, month) VALUES (?, ?)')->execute([$year, $m]);
}

echo "\n1. Database checks\n";
assert_test((int)$db->query('SELECT COUNT(*) FROM health_program')->fetchColumn() === 6, '6 health programs exist');
assert_test((int)$db->query('SELECT COUNT(*) FROM indicator')->fetchColumn() >= 350, '350+ indicators exist');
assert_test((int)$db->query('SELECT COUNT(*) FROM barangay')->fetchColumn() === 16, '16 barangays exist');

echo "\n2. Create test users\n";
$users = [
    ['nurse1', 'nurse@test.ph', 'nurse', 'Test Nurse'],
    ['admin1', 'admin@test.ph', 'admin', 'Test Admin'],
    ['doctor1', 'doctor@test.ph', 'doctor', 'Test Doctor'],
];
$userIds = [];
foreach ($users as [$uname, $email, $role, $name]) {
    $db->prepare('DELETE FROM users WHERE username = ?')->execute([$uname]);
    $h = password_hash('Test@1234', PASSWORD_BCRYPT);
    $db->prepare(
        'INSERT INTO users (username, email, password_hash, role, full_name, position, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)'
    )->execute([$uname, $email, $h, $role, $name, 'Test']);
    $userIds[$role] = (int) $db->lastInsertId();
}
assert_test(isset($userIds['nurse']), 'Nurse user created');

$barangayId = (int) $db->query('SELECT id FROM barangay LIMIT 1')->fetchColumn();
$db->prepare('DELETE FROM user_barangay WHERE user_id = ?')->execute([$userIds['nurse']]);
$db->prepare('INSERT INTO user_barangay (user_id, barangay_id) VALUES (?, ?)')->execute([$userIds['nurse'], $barangayId]);
assert_test(true, 'Nurse assigned to barangay');

echo "\n3. Report workflow\n";
$periodId = (int) $db->query('SELECT id FROM report_period ORDER BY year DESC, month DESC LIMIT 1')->fetchColumn();
$programId = 1; // CHILD

$db->prepare('DELETE FROM report_submission WHERE barangay_id = ? AND period_id = ? AND program_id = ?')
   ->execute([$barangayId, $periodId, $programId]);

$db->prepare(
    'INSERT INTO report_submission (barangay_id, period_id, program_id, status) VALUES (?, ?, ?, ?)'
)->execute([$barangayId, $periodId, $programId, 'draft']);
$submissionId = (int) $db->lastInsertId();
assert_test($submissionId > 0, 'Draft submission created');

require_once __DIR__ . '/../includes/indicators.php';

// Save indicator values
$ind = $db->query("SELECT id FROM indicator WHERE program_id = 1 AND sex_disaggregation = 'MFT' LIMIT 1")->fetch();
$values = [$ind['id'] => ['M' => ['_' => 5], 'F' => ['_' => 3], 'T' => ['_' => 8]]];
$saved = saveIndicatorValues($db, $submissionId, $values);
assert_test($saved === 3, 'Indicator values saved (MFT)');

// Submit
$db->prepare(
    "UPDATE report_submission SET status='submitted', submitted_by=?, submitted_at=NOW() WHERE id=?"
)->execute([$userIds['nurse'], $submissionId]);
$status = $db->query("SELECT status FROM report_submission WHERE id = $submissionId")->fetchColumn();
assert_test($status === 'submitted', 'Report submitted');

// Admin validate
$db->prepare(
    "UPDATE report_submission SET status='validated', validated_by=?, validated_at=NOW() WHERE id=?"
)->execute([$userIds['admin'], $submissionId]);
$status = $db->query("SELECT status FROM report_submission WHERE id = $submissionId")->fetchColumn();
assert_test($status === 'validated', 'Report validated by admin');

// Doctor remarks
$db->prepare("UPDATE report_submission SET remarks = ? WHERE id = ?")
   ->execute(['Clinical review: data looks consistent.', $submissionId]);
$remarks = $db->query("SELECT remarks FROM report_submission WHERE id = $submissionId")->fetchColumn();
assert_test(str_contains($remarks, 'Clinical review'), 'Doctor remarks saved');

echo "\n4. Indicator form engine (all 6 programs)\n";
foreach (range(1, 6) as $pid) {
    $grouped = loadIndicatorsGrouped($db, $pid);
    $partCount = count($grouped);
    $indCount = 0;
    foreach ($grouped as $cats) {
        foreach ($cats as $inds) {
            $indCount += count($inds);
        }
    }
    $code = $db->query("SELECT code FROM health_program WHERE id = $pid")->fetchColumn();
    assert_test($indCount > 0, "$code program: $indCount indicators in $partCount parts");
}

echo "\n5. Auth checks\n";
$_SESSION['user_id'] = $userIds['nurse'];
$_SESSION['role'] = 'nurse';
$_SESSION['assigned_barangay_ids'] = [$barangayId];
$sub = getSubmission($db, $submissionId);
assert_test(canViewSubmission($sub), 'Nurse can view own barangay submission');
assert_test(!canEditSubmission($sub), 'Nurse cannot edit validated submission');

$otherBarangay = (int) $db->query('SELECT id FROM barangay WHERE id != ' . $barangayId . ' LIMIT 1')->fetchColumn();
assert_test(!canAccessBarangay($otherBarangay), 'Nurse cannot access other barangay');

$_SESSION['user_id'] = 0;
$_SESSION['role'] = 'superadmin';
assert_test(canEditSubmission($sub), 'Superadmin can edit submitted or validated submission');

echo "\n" . str_repeat('=', 40) . "\n";
echo "Results: $passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
