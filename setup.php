<?php
/**
 * One-time setup: sets superadmin password and seeds report periods.
 * Run once after importing the database schema, then delete or restrict access.
 */
require_once __DIR__ . '/includes/init.php';

$messages = [];
$errors = [];

try {
    $db = getDB();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? 'Admin@123';
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'superadmin'");
            $stmt->execute([$hash]);
            $messages[] = 'Superadmin password updated successfully.';

            $year = (int) date('Y');
            for ($m = 1; $m <= 12; $m++) {
                $db->prepare('INSERT IGNORE INTO report_period (year, month) VALUES (?, ?)')
                   ->execute([$year, $m]);
            }
            $messages[] = "Report periods for $year seeded.";
        }
    }

    $userCount = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $periodCount = $db->query('SELECT COUNT(*) FROM report_period')->fetchColumn();
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage() . ' — Create database mho_db and import the SQL schema first.';
    $userCount = 0;
    $periodCount = 0;
}

$pageTitle = 'System Setup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — MHO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">MHO System Setup</h5>
        </div>
        <div class="card-body">
            <?php foreach ($messages as $msg): ?>
            <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endforeach; ?>
            <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>

            <p class="text-muted">Users: <strong><?= (int)$userCount ?></strong> |
               Periods: <strong><?= (int)$periodCount ?></strong></p>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Superadmin Password</label>
                    <input type="password" name="password" class="form-control" value="Admin@123" required minlength="8">
                    <div class="form-text">Default username: <code>superadmin</code></div>
                </div>
                <button type="submit" class="btn btn-primary">Initialize System</button>
                <a href="<?= e(baseUrl('login.php')) ?>" class="btn btn-outline-secondary ms-2">Go to Login</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
