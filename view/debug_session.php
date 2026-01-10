<?php
require __DIR__ . '/../app/bootstrap.php';

// Debug endpoint for OpenShift session troubleshooting
header('Content-Type: application/json; charset=UTF-8');

$debug = [
  'php_version' => PHP_VERSION,
  'session_status' => session_status(),
  'session_status_text' => match(session_status()) {
    PHP_SESSION_DISABLED => 'DISABLED',
    PHP_SESSION_NONE => 'NONE',
    PHP_SESSION_ACTIVE => 'ACTIVE',
    default => 'UNKNOWN'
  },
  'session_id' => session_id(),
  'session_name' => session_name(),
  'session_save_path' => session_save_path(),
  'session_data' => $_SESSION ?? [],
  'cookie_params' => session_get_cookie_params(),
  'environment' => [
    'openshift' => getenv('OPENSHIFT_BUILD_NAMESPACE') ?: 'no',
    'kubernetes' => getenv('KUBERNETES_SERVICE_HOST') ?: 'no',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'https' => $_SERVER['HTTPS'] ?? 'off',
  ],
  'temp_dirs' => [
    'sys_get_temp_dir' => [
      'path' => sys_get_temp_dir(),
      'exists' => is_dir(sys_get_temp_dir()),
      'writable' => is_writable(sys_get_temp_dir())
    ],
    'tmp' => [
      'path' => '/tmp',
      'exists' => is_dir('/tmp'),
      'writable' => is_writable('/tmp')
    ],
    'var_tmp' => [
      'path' => '/var/tmp',
      'exists' => is_dir('/var/tmp'),
      'writable' => is_writable('/var/tmp')
    ]
  ]
];

echo json_encode($debug, JSON_PRETTY_PRINT);