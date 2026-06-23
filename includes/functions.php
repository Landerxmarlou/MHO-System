<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function baseUrl(string $path = ''): string
{
    $base = defined('APP_BASE') ? APP_BASE : '';
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

function assetUrl(string $path): string
{
    return baseUrl('assets/' . ltrim($path, '/'));
}

function roleUrl(string $role, string $page = 'dashboard.php'): string
{
    return baseUrl($role . '/' . $page);
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrfToken(), $token);
}

function statusBadge(string $status): string
{
    $map = [
        'draft'     => 'secondary',
        'submitted' => 'warning',
        'validated' => 'success',
        'rejected'  => 'danger',
        'archived'  => 'dark',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
}

function submissionTimeliness(?string $submittedAt): ?string
{
    if ($submittedAt === null || trim($submittedAt) === '') {
        return null;
    }

    try {
        $date = new DateTime($submittedAt);
    } catch (Exception $e) {
        return null;
    }

    $day = (int) $date->format('j');

    return $day <= 25 ? 'on_time' : 'late';
}

function submissionConditionBadge(?string $submittedAt): string
{
    $condition = submissionTimeliness($submittedAt);
    if ($condition === null) {
        return '<span class="text-muted">—</span>';
    }

    if ($condition === 'on_time') {
        return '<span class="badge bg-success">On-time</span>';
    }

    return '<span class="badge bg-danger">Late</span>';
}

function monthName(int $month): string
{
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    return $months[$month] ?? (string) $month;
}

function periodLabel(int $year, int $month): string
{
    return monthName($month) . ' ' . $year;
}

function parseAgeGroups(string $ageDisaggregation): array
{
    if ($ageDisaggregation === 'NONE' || $ageDisaggregation === '') {
        return [null];
    }
    return array_map('trim', explode(',', $ageDisaggregation));
}

function sexLabels(): array
{
    return ['M' => 'Male', 'F' => 'Female', 'T' => 'Total'];
}

function logAudit(PDO $db, string $table, int $recordId, string $action, ?array $oldValue, ?array $newValue): void
{
    $stmt = $db->prepare(
        'INSERT INTO audit_log (table_name, record_id, action, changed_by, old_value, new_value)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $table,
        $recordId,
        $action,
        $_SESSION['user_id'] ?? null,
        $oldValue ? json_encode($oldValue) : null,
        $newValue ? json_encode($newValue) : null,
    ]);
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function barangayCodeFromName(string $name): string
{
    $letters = preg_replace('/[^A-Za-z]/', '', $name);
    if ($letters === '') {
        return 'BRG';
    }
    return strtoupper(substr($letters, 0, 3));
}

function generateReportCode(PDO $db, int $submissionId): string
{
    $stmt = $db->prepare(
        'SELECT b.code AS barangay_code, b.name AS barangay_name, hp.code AS program_code,
                rp.year, rp.month
         FROM report_submission rs
         JOIN barangay b ON b.id = rs.barangay_id
         JOIN health_program hp ON hp.id = rs.program_id
         JOIN report_period rp ON rp.id = rs.period_id
         WHERE rs.id = ?'
    );
    $stmt->execute([$submissionId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new InvalidArgumentException('Submission not found.');
    }

    $brgyCode = trim((string) ($row['barangay_code'] ?? ''));
    if ($brgyCode === '') {
        $brgyCode = barangayCodeFromName($row['barangay_name']);
    }

    $programCode = trim((string) ($row['program_code'] ?? ''));
    if ($programCode === '') {
        $programCode = 'RPT';
    }

    $period = sprintf('%04d%02d', (int) $row['year'], (int) $row['month']);

    return strtoupper($brgyCode) . '-' . strtoupper($programCode) . '-' . $period;
}

function appRoot(): string
{
    return rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..') ?: ''), '/');
}

function profileUploadDir(): string
{
    return appRoot() . '/uploads/profiles';
}

function profileImageUrl(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    return baseUrl($path);
}

function deleteProfileImage(?string $path): void
{
    if ($path === null || $path === '') {
        return;
    }
    $uploadDir = realpath(profileUploadDir());
    if ($uploadDir === false) {
        return;
    }
    $fullPath = realpath(appRoot() . '/' . ltrim($path, '/'));
    if ($fullPath !== false && str_starts_with($fullPath, $uploadDir)) {
        @unlink($fullPath);
    }
}

function saveProfileImage(int $userId, array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Failed to upload profile photo.'];
    }
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['path' => null, 'error' => 'Profile photo must be 2 MB or smaller.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['path' => null, 'error' => 'Profile photo must be a JPEG, PNG, GIF, or WebP image.'];
    }

    $dir = profileUploadDir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['path' => null, 'error' => 'Could not create upload directory.'];
    }

    $filename = 'user_' . $userId . '_' . time() . '.' . $allowed[$mime];
    $relative = 'uploads/profiles/' . $filename;
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['path' => null, 'error' => 'Failed to save profile photo.'];
    }

    return ['path' => $relative, 'error' => null];
}
