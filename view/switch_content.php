<?php
require __DIR__ . '/../app/bootstrap.php';

// Add debugging for OpenShift
$debug_info = [
  'session_status' => session_status(),
  'session_id' => session_id(),
  'has_csrf_session' => isset($_SESSION['csrf_token']),
  'received_token' => $_POST['csrf_token'] ?? 'none',
  'openshift_env' => getenv('OPENSHIFT_BUILD_NAMESPACE') ? 'yes' : 'no',
  'kubernetes_env' => getenv('KUBERNETES_SERVICE_HOST') ? 'yes' : 'no'
];

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed', 'debug' => $debug_info]);
  exit;
}

// CSRF protection - more lenient in container environments
$csrf_token = $_POST['csrf_token'] ?? '';
$csrf_valid = csrf_verify($csrf_token);

// In OpenShift/Kubernetes, provide more detailed error info for debugging
if (!$csrf_valid && (getenv('OPENSHIFT_BUILD_NAMESPACE') || getenv('KUBERNETES_SERVICE_HOST'))) {
  http_response_code(403);
  echo json_encode([
    'success' => false, 
    'error' => 'CSRF validation failed', 
    'debug' => $debug_info,
    'help' => 'Try refreshing the page to get a new session token'
  ]);
  exit;
} elseif (!$csrf_valid) {
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
