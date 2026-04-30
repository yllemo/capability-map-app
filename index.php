<?php
require __DIR__ . '/app/bootstrap.php';

$default = cfg('app')['default_mode'] ?? 'view';
$query = $_SERVER['QUERY_STRING'] ?? '';
$querySuffix = $query !== '' ? ('?' . $query) : '';

if ($default === 'editor') {
  header('Location: ' . base_path('editor/index.php') . $querySuffix);
  exit;
}
header('Location: ' . base_path('view/index.php') . $querySuffix);
exit;
