<?php
require __DIR__ . '/_auth.php';
require_auth();

use App\PathGuard;

// CSRF protection
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo 'CSRF validation failed';
  exit;
}

// Use selected content directory
$contentDir = get_content_dir();

$rel = (string)($_POST['file'] ?? '');
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

// Delete the file
if (@unlink($abs)) {
  // Log the delete action
  use App\Logger;
  Logger::audit('capability_deleted', ['file' => $rel]);

  // Try to remove empty parent directories
  $dir = dirname($abs);
  while ($dir !== $contentDir && is_dir($dir) && count(scandir($dir)) === 2) {
    @rmdir($dir);
    $dir = dirname($dir);
  }

  header('Location: index.php?success=deleted');
  exit;
} else {
  http_response_code(500);
  echo 'Failed to delete file';
  exit;
}
