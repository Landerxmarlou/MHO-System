<?php
require_once __DIR__ . '/../includes/init.php';
requireRole(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$url = roleUrl('admin', 'submissions.php' . ($id ? '?id=' . $id : ''));
redirect($url);
