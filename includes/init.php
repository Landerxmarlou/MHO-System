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

define('APP_SHORT_NAME', 'HEARTs');
define('APP_OFFICE_NAME', 'Municipal Health Office');
define('APP_TAGLINE', 'Health Entry Access Records and Tracking System');
define('APP_DESCRIPTION', 'A centralized digital system for the Municipal Health Office that helps manage, store, track, and monitor health records, indicators, and reports efficiently. Improves data accuracy, reporting processes, and coordination across all health programs and barangays.');
define('APP_NAME', 'HEARTs-Health Entry Access Records and Tracking System');
define('APP_LOCATION', 'Municipal Health Office — Cauayan, Isabela');
