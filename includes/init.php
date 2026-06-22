<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
$appRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..') ?: ''), '/');
$basePath = '';
if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot)) {
    $basePath = substr($appRoot, strlen($docRoot));
}
if (!defined('APP_BASE')) {
    define('APP_BASE', $basePath);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

define('APP_NAME', 'MHO Record Management System');
define('APP_LOCATION', 'Municipal Health Office — Cauayan, Isabela');
