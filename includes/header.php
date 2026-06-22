<?php
$pageTitle = $pageTitle ?? APP_NAME;
$role = $_SESSION['role'] ?? '';
$navItems = [];
$navSections = [];

switch ($role) {
    case 'superadmin':
        $navSections = [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Dashboard', 'url' => roleUrl('superadmin', 'dashboard.php'), 'icon' => 'bi-speedometer2'],
                ],
            ],
            [
                'title' => 'Records',
                'items' => [
                    ['label' => 'Submissions', 'url' => roleUrl('superadmin', 'submissions.php'), 'icon' => 'bi-file-earmark-text'],
                    ['label' => 'Users', 'url' => roleUrl('superadmin', 'users.php'), 'icon' => 'bi-people'],
                    ['label' => 'Barangays', 'url' => roleUrl('superadmin', 'barangays.php'), 'icon' => 'bi-geo-alt'],
                    ['label' => 'Periods', 'url' => roleUrl('superadmin', 'periods.php'), 'icon' => 'bi-calendar3'],
                    ['label' => 'Programs', 'url' => roleUrl('superadmin', 'programs.php'), 'icon' => 'bi-journal-medical'],
                ],
            ],
            [
                'title' => 'System',
                'items' => [
                    ['label' => 'Audit log', 'url' => roleUrl('superadmin', 'audit-log.php'), 'icon' => 'bi-shield-check'],
                ],
            ],
        ];
        break;
    case 'admin':
        $navItems = [
            ['label' => 'Dashboard', 'url' => roleUrl('admin', 'dashboard.php'), 'icon' => 'bi-speedometer2'],
            ['label' => 'Submissions', 'url' => roleUrl('admin', 'submissions.php'), 'icon' => 'bi-inbox'],
            ['label' => 'Validate', 'url' => roleUrl('admin', 'validate.php'), 'icon' => 'bi-check2-circle'],
            ['label' => 'Targets', 'url' => roleUrl('admin', 'targets.php'), 'icon' => 'bi-bullseye'],
            ['label' => 'Reports', 'url' => roleUrl('admin', 'reports.php'), 'icon' => 'bi-bar-chart'],
        ];
        break;
    case 'doctor':
        $navItems = [
            ['label' => 'Dashboard', 'url' => roleUrl('doctor', 'dashboard.php'), 'icon' => 'bi-speedometer2'],
            ['label' => 'Submissions', 'url' => roleUrl('doctor', 'submissions.php'), 'icon' => 'bi-inbox'],
        ];
        break;
    case 'nurse':
        $navItems = [
            ['label' => 'Dashboard', 'url' => roleUrl('nurse', 'dashboard.php'), 'icon' => 'bi-speedometer2'],
            ['label' => 'My Reports', 'url' => roleUrl('nurse', 'submissions.php'), 'icon' => 'bi-file-earmark-medical'],
            ['label' => 'New Report', 'url' => roleUrl('nurse', 'new-report.php'), 'icon' => 'bi-plus-circle'],
        ];
        break;
}

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('css/base.css')) ?>" rel="stylesheet">
    <?php
    $roleStylesheets = ['superadmin', 'admin', 'doctor', 'nurse'];
    if (in_array($role, $roleStylesheets, true)):
    ?>
    <link href="<?= e(assetUrl('css/' . $role . '.css')) ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if ($role === 'superadmin'): ?>
    <link href="<?= e(assetUrl('css/encode-report.css')) ?>" rel="stylesheet">
    <?php endif; ?>
    <?php if (!empty($extraStyles)): foreach ((array)$extraStyles as $style): ?>
    <link href="<?= e(assetUrl('css/' . $style)) ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
</head>
<body class="role-<?= e($role ?: 'guest') ?><?= !empty($bodyClass) ? ' ' . e($bodyClass) : '' ?>">
<?php if (isLoggedIn()): ?>
<div class="d-flex" id="wrapper">
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <div class="sidebar-brand-mark">
                    <img src="<?= e(baseUrl('Photo/RHULOGO.jpg')) ?>" alt="MHO logo">
                </div>
                <div class="sidebar-brand-copy">
                    <div class="sidebar-brand-name">MHO Solano</div>
                    <div class="sidebar-brand-subtitle">Record Management</div>
                </div>
            </div>
        </div>
        <div class="sidebar-scroll">
            <?php $sections = $role === 'superadmin' ? $navSections : [['title' => null, 'items' => $navItems]]; ?>
            <?php foreach ($sections as $section): ?>
                <?php if (!empty($section['title'])): ?>
                <div class="sidebar-section-title"><?= e($section['title']) ?></div>
                <?php endif; ?>
                <ul class="nav flex-column sidebar-nav">
                    <?php foreach ($section['items'] as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link sidebar-link <?= $currentPage === basename($item['url']) ? 'active' : '' ?>"
                           href="<?= e($item['url']) ?>">
                            <span class="sidebar-item-icon"><i class="bi <?= e($item['icon']) ?>"></i></span>
                            <span class="sidebar-link-text"><?= e($item['label']) ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer">
            <div class="sidebar-welcome">
                Welcome, <?= e($_SESSION['username'] ?? 'User') ?>!
            </div>
            <a href="<?= e(baseUrl('logout.php')) ?>" class="sidebar-link logout-link">
                <span class="sidebar-item-icon"><i class="bi bi-box-arrow-right"></i></span>
                <span class="sidebar-link-text">Logout</span>
            </a>
        </div>
    </nav>
    <div id="page-content" class="flex-grow-1">
        <main class="container-fluid p-4<?= $role === 'superadmin' ? ' superadmin-main' : '' ?>">
            <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none mb-3" id="sidebarToggle" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <?php
            if ($role === 'superadmin') {
                require_once __DIR__ . '/superadmin-ui.php';
            }
            $flash = getFlash(); if ($flash):
            ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>      
            <?php endif; ?>
<?php else: ?>
<main>
<?php endif; ?>
