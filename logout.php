<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = (int) ($_SESSION['user_id'] ?? 0);

require_once __DIR__ . '/includes/init.php';
logout(getDB(), $userId > 0 ? $userId : null);
redirect(baseUrl('index.php'));
