<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect(roleUrl($_SESSION['role'], 'dashboard.php'));
}

$error = '';
$barangays = [];
$roles = ['nurse', 'doctor', 'admin', 'superadmin'];

try {
    $db = getDB();
    $barangays = $db->query('SELECT id, name FROM barangay WHERE is_active = 1 ORDER BY name')->fetchAll();
} catch (PDOException $e) {
    $error = 'Database connection failed. Please ensure mho_db is set up.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!verifyCsrf()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = register($db, $_POST);
        if ($result['success']) {
            setFlash('success', $result['message']);
            redirect(baseUrl('login.php'));
        }
        $error = $result['message'];
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<div class="login-wrapper d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="card shadow login-card login-card-wide border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-person-plus text-primary" style="font-size:3rem;"></i>
                <h4 class="mt-2 fw-bold">Create Account</h4>
                <p class="text-muted small">Register for <?= e(APP_NAME) ?></p>
            </div>
            <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required autofocus
                           value="<?= e($_POST['full_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" required
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" id="roleSelect" required>
                            <option value="">Select role</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r) ?>" <?= ($_POST['role'] ?? '') === $r ? 'selected' : '' ?>>
                                <?= ucfirst($r) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" class="form-control" placeholder="e.g. Public Health Nurse"
                               value="<?= e($_POST['position'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="mb-3" id="barangaySection">
                    <label class="form-label">Assigned Barangay *</label>
                    <select name="barangay_id" class="form-select" id="barangaySelect">
                        <option value="">Select barangay</option>
                        <?php foreach ($barangays as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"
                            <?= (int) ($_POST['barangay_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password *</label>
                    <div class="input-group">
                        <input type="password" name="password" id="registerPassword" class="form-control" required minlength="8">
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="registerPassword"
                                aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">At least 8 characters</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirm Password *</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-control" required minlength="8">
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="confirmPassword"
                                aria-label="Toggle password visibility" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-person-check me-1"></i> Register
                </button>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">
                Already have an account?
                <a href="<?= e(baseUrl('login.php')) ?>">Sign in</a>
            </p>
        </div>
    </div>
</div>
<script>
document.getElementById('roleSelect').addEventListener('change', function () {
    const isNurse = this.value === 'nurse';
    const section = document.getElementById('barangaySection');
    const select = document.getElementById('barangaySelect');
    section.style.display = isNurse ? 'block' : 'none';
    select.required = isNurse;
    if (!isNurse) {
        select.value = '';
    }
});
document.getElementById('roleSelect').dispatchEvent(new Event('change'));
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
