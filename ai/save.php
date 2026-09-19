<?php
require __DIR__ . '/../editor/_auth.php';
require_edit();

use App\PathGuard;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  exit('CSRF validation failed');
}

$file = trim((string)($_POST['file'] ?? ''));
$markdown = (string)($_POST['markdown'] ?? '');
if ($file === '') {
  http_response_code(400);
  exit('Missing file');
}

$contentDir = get_content_dir();
try {
  $abs = PathGuard::safeJoin($contentDir, $file);
} catch (Throwable $e) {
  http_response_code(400);
  exit('Invalid path');
}

if (!is_file($abs)) {
  http_response_code(404);
  exit('File not found');
}

file_put_contents($abs, $markdown);
header('Location: ' . base_path('ai/index.php?file=' . rawurlencode($file) . '&saved=1'));
exit;
