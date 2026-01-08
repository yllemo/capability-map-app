<?php
require __DIR__ . '/_auth.php';
require_auth();

use App\PathGuard;
use App\Logger;

// CSRF protection
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo 'CSRF validation failed';
  exit;
}

// Use selected content directory
$contentDir = get_content_dir();

$rel = (string)($_POST['file'] ?? '');
$content = (string)($_POST['content'] ?? '');

if ($rel === '') { 
  http_response_code(400); 
  echo 'Missing file'; 
  exit; 
}

if ($content === '') {
  http_response_code(400); 
  echo 'Empty content not allowed'; 
  exit; 
}

try {
  $abs = PathGuard::safeJoin($contentDir, $rel);
} catch (Throwable $e) {
  http_response_code(400); 
  echo 'Invalid path'; 
  exit;
}

// Ensure UTF-8 encoding without BOM
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

// Create directory if needed
@mkdir(dirname($abs), 0775, true);

// Save the raw content
file_put_contents($abs, $content, LOCK_EX);

// Log the save action
Logger::audit('capability_raw_saved', ['file' => $rel]);

// Redirect back to editor with success message
header('Location: index.php?file=' . rawurlencode($rel) . '&success=saved');
exit;