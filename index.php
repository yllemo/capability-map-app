<?php
require __DIR__ . '/app/bootstrap.php';

$default = cfg('app')['default_mode'] ?? 'view';
if ($default === 'editor') {
  header('Location: ' . base_path('editor/index.php'));
  exit;
}
header('Location: ' . base_path('view/index.php'));
exit;
