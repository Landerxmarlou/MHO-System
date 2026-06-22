<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect(roleUrl($_SESSION['role'], 'dashboard.php'));
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Please enter username and password.';
        } else {
            try {
                $db = getDB();
                $loginError = login($db, $username, $password);
                if ($loginError === null) {
                    redirect(roleUrl($_SESSION['role'], 'dashboard.php'));
                }
                $error = $loginError;
            } catch (PDOException $e) {
                $error = 'Database connection failed. Please ensure mho_db is set up.';
            }
        }
    }
}

$pageTitle = 'Login';
$flash = getFlash();
if ($flash && $flash['type'] === 'success') {
    $success = $flash['message'];
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow login-card border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-hospital text-primary" style="font-size:3rem;"></i>
                <h4 class="mt-2 fw-bold">MHO Record Management</h4>
                <p>Municipal Health Office — Solano, Nueva Vizcaya</p>
            </div>
            <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus
                               value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="loginPassword" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="loginPassword"
                                aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">
                Don't have an account?
                <a href="<?= e(baseUrl('register.php')) ?>">Register</a>
            </p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
