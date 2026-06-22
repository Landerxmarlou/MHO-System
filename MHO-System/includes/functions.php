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
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e(ucfirst($status)) . '</span>';
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
