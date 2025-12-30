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

$oldRel = (string)($_POST['old_file'] ?? '');
$newName = trim((string)($_POST['new_name'] ?? ''));

if ($oldRel === '' || $newName === '') {
  http_response_code(400);
  echo 'Missing parameters';
  exit;
}

try {
  $oldAbs = PathGuard::safeJoin($contentDir, $oldRel);
} catch (Throwable $e) {
  http_response_code(400);
  echo 'Invalid path';
  exit;
}

if (!is_file($oldAbs)) {
  http_response_code(404);
  echo 'File not found';
  exit;
}

// Generate new filename
$dir = dirname($oldRel);
$newRel = ($dir !== '.' ? $dir . '/' : '') . $newName;

// Ensure .md extension
if (!str_ends_with($newRel, '.md')) {
  $newRel .= '.md';
}

try {
  $newAbs = PathGuard::safeJoin($contentDir, $newRel);
} catch (Throwable $e) {
  http_response_code(400);
  echo 'Invalid new path';
  exit;
}

// Check if target already exists
if (file_exists($newAbs)) {
  header('Location: index.php?file=' . rawurlencode($oldRel) . '&error=file_exists');
  exit;
}

// Rename the file
if (@rename($oldAbs, $newAbs)) {
  Logger::audit('capability_renamed', ['from' => $oldRel, 'to' => $newRel]);
  header('Location: index.php?file=' . rawurlencode($newRel) . '&success=renamed');
  exit;
} else {
  http_response_code(500);
  echo 'Failed to rename file';
  exit;
}
