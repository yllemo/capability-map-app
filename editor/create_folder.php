<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

// CSRF protection
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
  exit;
}

$key = trim($_POST['key'] ?? '');
$label = trim($_POST['label'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate input
if ($key === '' || $label === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Folder-nyckel och visningsnamn krävs']);
  exit;
}

// Validate key format (only lowercase letters, numbers, underscore, dash)
if (!preg_match('/^[a-z0-9_\-]+$/', $key)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Folder-nyckel får endast innehålla små bokstäver, siffror, - och _']);
  exit;
}

// New maps are always created under the common content root.
$lock = null;
$newDirPath = null;
$created = false;
try {
  $root = get_content_root();
  if ($root === null) throw new RuntimeException('Kör först Flytta innehåll till /content i editorn.');
  $lock = fopen(__DIR__ . '/../content/.capmap-migration.lock', 'c');
  if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('En annan katalogändring pågår. Försök igen.');
  $configPath = App\ContentMigration::configPath(dirname(__DIR__));
  $config = json_decode((string)file_get_contents($configPath), true, 32, JSON_THROW_ON_ERROR);
  if (isset($config['content_dirs'][$key])) throw new RuntimeException('Folder-nyckeln används redan.');
  $newDirPath = rtrim($root, '/\\') . '/' . $key;
  if (file_exists($newDirPath) || is_link($newDirPath)) throw new RuntimeException('Katalogen finns redan.');
  if (!mkdir($newDirPath, 0775)) throw new RuntimeException('Kunde inte skapa katalogen.');
  $created = true;
  $config['content_dirs'][$key] = ['folder' => $key, 'label' => $label, 'description' => $description];
  App\ContentMigration::writeConfig(dirname(__DIR__), $config);
  echo json_encode(['success' => true, 'key' => $key, 'label' => $label, 'path' => $newDirPath]);
} catch (Throwable $e) {
  if ($created) @rmdir($newDirPath);
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
  if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
}
