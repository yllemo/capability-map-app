<?php
require __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=UTF-8');
if (!is_authed()) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Logga in i editorn igen.']);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}
$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !csrf_verify($token)) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'Sessionen har gått ut. Ladda om sidan och försök igen.']);
  exit;
}
try {
  foreach (['key', 'label', 'description'] as $field) {
    if (!is_string($_POST[$field] ?? '')) throw new InvalidArgumentException('Fälten måste innehålla text.');
  }
  $key = trim($_POST['key'] ?? '');
  $label = trim($_POST['label'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $path = App\ContentFolders::create(dirname(__DIR__), cfg('app'), $key, $label, $description);
  // The next request reloads config; its map parameter selects the new folder.
  echo json_encode(['success' => true, 'key' => $key, 'label' => $label, 'path' => $path, 'redirect' => base_path('editor/index.php?map=' . rawurlencode($key))]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
