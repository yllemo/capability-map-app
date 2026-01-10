<?php
require __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');

// Allow GET for easy debugging in OpenShift
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reset'])) {
  // Force session regeneration
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
    
    // Clear all session data
    $_SESSION = [];
    
    // Generate new CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    echo json_encode([
      'success' => true,
      'message' => 'Session reset complete',
      'new_session_id' => session_id(),
      'csrf_token' => $_SESSION['csrf_token']
    ]);
  } else {
    echo json_encode([
      'success' => false,
      'message' => 'No active session to reset'
    ]);
  }
  exit;
}

// Return session info
echo json_encode([
  'session_active' => session_status() === PHP_SESSION_ACTIVE,
  'session_id' => session_id(),
  'csrf_token' => $_SESSION['csrf_token'] ?? null,
  'usage' => 'Add ?reset=1 to reset session'
]);