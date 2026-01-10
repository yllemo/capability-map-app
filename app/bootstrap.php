<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Stockholm');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Secure session configuration
if (session_status() === PHP_SESSION_NONE) {
  // OpenShift compatibility: Less strict cookie settings for container environments
  if (getenv('OPENSHIFT_BUILD_NAMESPACE') || getenv('KUBERNETES_SERVICE_HOST')) {
    // In OpenShift/Kubernetes, use less strict settings
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax'); // Less strict for containerized environments
    // Don't force secure cookies in container environments where SSL termination happens at proxy
  } else {
    // Standard settings for other environments
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
      ini_set('session.cookie_secure', '1');
    }
  }
  ini_set('session.use_strict_mode', '1');
  session_start();
}

function cfg(string $name): array {
  static $cache = [];
  if (!isset($cache[$name])) {
    $path = __DIR__ . '/../config/' . $name . '.php';
    $cache[$name] = file_exists($path) ? require $path : [];
  }
  return $cache[$name];
}

function base_path(string $path = ''): string {
  $bp = rtrim(cfg('app')['base_path'] ?? '', '/');
  $path = ltrim($path, '/');
  return $bp . '/' . $path;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/**
 * Get available content directories
 * @return array
 */
function get_content_dirs(): array {
  $dirs = cfg('app')['content_dirs'] ?? [];
  if (empty($dirs)) {
    // Fallback to single content_dir for backwards compatibility
    $contentDir = cfg('app')['content_dir'] ?? __DIR__ . '/../content';
    $dirs = [
      'content' => [
        'path' => $contentDir,
        'label' => 'Content',
        'description' => 'Default content directory',
      ],
    ];
  }
  return $dirs;
}

/**
 * Get the currently selected content directory key
 * @return string
 */
function get_selected_content_key(): string {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  $dirs = get_content_dirs();
  $selected = $_SESSION['content_dir_key'] ?? '';

  // Validate that selected key exists
  if ($selected && isset($dirs[$selected])) {
    return $selected;
  }

  // Return first available directory
  return array_key_first($dirs) ?? 'content';
}

/**
 * Get the currently selected content directory path
 * @return string
 */
function get_content_dir(): string {
  $key = get_selected_content_key();
  $dirs = get_content_dirs();
  return $dirs[$key]['path'] ?? cfg('app')['content_dir'] ?? __DIR__ . '/../content';
}

/**
 * Set the selected content directory
 * @param string $key
 * @return bool
 */
function set_content_dir(string $key): bool {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  $dirs = get_content_dirs();
  if (!isset($dirs[$key])) {
    return false;
  }

  $_SESSION['content_dir_key'] = $key;
  return true;
}

/**
 * Generate CSRF token
 * @return string
 */
function csrf_token(): string {
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function csrf_verify(string $token): bool {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token HTML input field
 * @return string
 */
function csrf_field(): string {
  return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

spl_autoload_register(function($class){
  $prefix = 'App\\';
  if (str_starts_with($class, $prefix)) {
    $rel = substr($class, strlen($prefix));
    $file = __DIR__ . '/lib/' . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) require $file;
  }
});
