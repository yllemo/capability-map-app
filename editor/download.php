<?php
require __DIR__ . '/_auth.php';
require_auth();

use App\PathGuard;

$app = cfg('app');

// Use selected content directory
$contentDir = get_content_dir();

$rel = (string)($_GET['file'] ?? '');
if ($rel === '') {
  http_response_code(400);
  echo 'Missing file parameter';
  exit;
}

try {
  $abs = PathGuard::safeJoin($contentDir, $rel);
} catch (Throwable $e) {
  http_response_code(400);
  echo 'Invalid path';
  exit;
}

if (!is_file($abs)) {
  http_response_code(404);
  echo 'File not found';
  exit;
}

// Read the file content
$content = file_get_contents($abs);
if ($content === false) {
  http_response_code(500);
  echo 'Failed to read file';
  exit;
}

// Set headers for download
$filename = basename($abs);
header('Content-Type: text/markdown; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output the file
echo $content;
exit;
