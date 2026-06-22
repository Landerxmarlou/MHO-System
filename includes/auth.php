<?php

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'position'  => $_SESSION['position'] ?? '',
    ];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(baseUrl('index.php'));
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        setFlash('danger', 'Access denied.');
        redirect(baseUrl('index.php'));
    }
}

function loadUserBarangays(PDO $db, int $userId): array
{
    $stmt = $db->prepare(
        'SELECT b.id, b.name FROM user_barangay ub
         JOIN barangay b ON b.id = ub.barangay_id
         WHERE ub.user_id = ? AND b.is_active = 1
         ORDER BY b.name'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getAssignedBarangayIds(): array
{
    return $_SESSION['assigned_barangay_ids'] ?? [];
}

function canAccessBarangay(int $barangayId): bool
{
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['superadmin', 'admin', 'doctor'], true)) {
        return true;
    }
    if ($role === 'nurse') {
        return in_array($barangayId, getAssignedBarangayIds(), true);
    }
    return false;
}

function login(PDO $db, string $username, string $password): ?string
{
    $stmt = $db->prepare(
        'SELECT id, username, email, password_hash, role, full_name, position, is_active, is_logged_in
         FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return 'Invalid username or password.';
    }

    if (!(int) $user['is_active']) {
        return 'Your account is pending admin approval. Please contact an administrator.';
    }

    if ((int) ($user['is_logged_in'] ?? 0) === 1) {
        return 'This account is already signed in on another device or browser. Please sign out there first, or contact an administrator.';
    }

    $hash = $user['password_hash'] ?? '';
    if ($hash === '' || str_contains($hash, 'placeholder')) {
        return 'System password not set. Open setup.php to initialize the admin account.';
    }

    if (!password_verify($password, $hash)) {
        return 'Invalid username or password.';
    }

    $claim = $db->prepare(
        'UPDATE users SET is_logged_in = 1, last_login_at = NOW()
         WHERE id = ? AND is_logged_in = 0 AND is_active = 1'
    );
    $claim->execute([(int) $user['id']]);
    if ($claim->rowCount() === 0) {
        return 'This account is already signed in on another device or browser. Please sign out there first, or contact an administrator.';
    }

    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['position']  = $user['position'] ?? '';

    if ($user['role'] === 'nurse') {
        $barangays = loadUserBarangays($db, (int) $user['id']);
        $_SESSION['assigned_barangay_ids'] = array_column($barangays, 'id');
        $_SESSION['assigned_barangays']    = $barangays;
    } else {
        $_SESSION['assigned_barangay_ids'] = [];
        $_SESSION['assigned_barangays']    = [];
    }

    return null;
}

function register(PDO $db, array $data): array
{
    $username        = trim($data['username'] ?? '');
    $email           = trim($data['email'] ?? '');
    $fullName        = trim($data['full_name'] ?? '');
    $password        = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';
    $role            = $data['role'] ?? '';
    $position        = trim($data['position'] ?? '');
    $phone           = trim($data['phone'] ?? '');
    $barangayId      = (int) ($data['barangay_id'] ?? 0);

    $validRoles = ['superadmin', 'admin', 'doctor', 'nurse'];

    if ($username === '' || $email === '' || $fullName === '' || $password === '' || $role === '') {
        return ['success' => false, 'message' => 'Please fill in all required fields.'];
    }
    if (!in_array($role, $validRoles, true)) {
        return ['success' => false, 'message' => 'Please select a valid role.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }
    if ($role === 'nurse' && $barangayId <= 0) {
        return ['success' => false, 'message' => 'Please select your assigned barangay.'];
    }

    if ($role === 'nurse') {
        $stmt = $db->prepare('SELECT id FROM barangay WHERE id = ? AND is_active = 1');
        $stmt->execute([$barangayId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Invalid barangay selected.'];
        }
    }

    try {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash, role, full_name, position, phone, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$username, $email, $hash, $role, $fullName, $position, $phone]);
        $userId = (int) $db->lastInsertId();

        if ($role === 'nurse') {
            $db->prepare('INSERT INTO user_barangay (user_id, barangay_id) VALUES (?, ?)')
               ->execute([$userId, $barangayId]);
        }

        logAudit($db, 'users', $userId, 'INSERT', null, [
            'username' => $username,
            'role'     => $role,
            'source'   => 'registration',
        ]);

        return [
            'success' => true,
            'message' => 'Registration successful! You can now sign in with your account.',
        ];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Username or email may already be taken.'];
    }
}

function clearUserLoginFlag(PDO $db, int $userId): void
{
    $db->prepare('UPDATE users SET is_logged_in = 0 WHERE id = ?')->execute([$userId]);
}

function destroySessionOnly(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function validateActiveLogin(PDO $db): void
{
    if (!isLoggedIn()) {
        return;
    }

    $stmt = $db->prepare('SELECT is_logged_in, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !(int) $user['is_active'] || !(int) ($user['is_logged_in'] ?? 0)) {
        destroySessionOnly();
        setFlash('warning', 'Your session has ended. Please sign in again.');
        redirect(baseUrl('login.php'));
    }
}

function logout(?PDO $db = null): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($db === null) {
        try {
            $db = getDB();
        } catch (Throwable $e) {
            $db = null;
        }
    }

    if ($db && $userId > 0) {
        clearUserLoginFlag($db, $userId);
    }

    destroySessionOnly();
}

function getSubmission(PDO $db, int $id): ?array
{
    $stmt = $db->prepare(
        'SELECT rs.*, b.name AS barangay_name, b.code AS barangay_code, hp.code AS program_code, hp.name AS program_name,
                rp.year, rp.month, rp.period_label
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         WHERE rs.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function canEditSubmission(array $submission): bool
{
    $role = $_SESSION['role'] ?? '';
    if ($role === 'superadmin') {
        return true;
    }
    if ($role === 'nurse') {
        if (!canAccessBarangay((int) $submission['barangay_id'])) {
            return false;
        }
        return in_array($submission['status'], ['draft', 'rejected'], true);
    }
    return false;
}

function canViewSubmission(array $submission): bool
{
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['superadmin', 'admin', 'doctor'], true)) {
        return true;
    }
    if ($role === 'nurse') {
        return canAccessBarangay((int) $submission['barangay_id']);
    }
    return false;
}

try {
    validateActiveLogin(getDB());
} catch (Throwable $e) {
    // Database unavailable on public pages; skip session validation.
}
