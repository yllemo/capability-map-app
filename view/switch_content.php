<?php
require __DIR__ . '/../app/bootstrap.php';
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
if ($key === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing key parameter']);
  exit;
}

if (!set_content_dir($key)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Invalid directory key']);
  exit;
}

$dirs = get_content_dirs();
echo json_encode([
  'success' => true,
  'key' => $key,
  'label' => $dirs[$key]['label'] ?? $key,
]);
exit;
