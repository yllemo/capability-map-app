<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Stockholm');
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

function load_env_file(string $path): void {
  if (!is_file($path) || !is_readable($path)) {
    return;
  }

  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!is_array($lines)) {
    return;
  }

  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    if (!str_contains($line, '=')) {
      continue;
    }

    [$key, $value] = array_map('trim', explode('=', $line, 2));
    if ($key === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1) {
      continue;
    }

    // Strip matching surrounding quotes.
    if (
      (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
      (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
      $value = substr($value, 1, -1);
    }

    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
  }
}

load_env_file(__DIR__ . '/../config/.env');

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
    $contentConfigPath = is_file(__DIR__ . '/../content/.capmap-config.json')
      ? __DIR__ . '/../content/.capmap-config.json'
      : __DIR__ . '/../config/content.local.json';
    if ($name === 'app' && is_file($contentConfigPath)) {
      $contentConfig = json_decode((string)file_get_contents($contentConfigPath), true, 32, JSON_THROW_ON_ERROR);
      $root = __DIR__ . '/../' . $contentConfig['content_root'];
      $cache[$name]['content_root'] = $root;
      $cache[$name]['content_dirs'] = $contentConfig['content_dirs'];
      foreach ($cache[$name]['content_dirs'] as &$dir) {
        $dir['path'] = $root . '/' . $dir['folder'];
      }
      unset($dir);
    }
  }
  $admin = App\AdminSettings::read();
  $result = $cache[$name];
  if ($name === 'auth') {
    foreach ($admin['users'] ?? [] as $username => $user) {
      if ($user === null) unset($result['users'][$username]);
      else $result['users'][$username] = $user;
    }
  }
  if ($name === 'app') $result = array_replace($result, $admin['app'] ?? []);
  return $result;
}

function base_path(string $path = ''): string {
  $bp = rtrim(cfg('app')['base_path'] ?? '', '/');
  $path = ltrim($path, '/');
  return $bp . '/' . $path;
}

function app_base_url(): string {
  $scheme = 'http';
  if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $scheme = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
    $scheme = trim($scheme);
  } elseif (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
  ) {
    $scheme = 'https';
  }

  $host = (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
  $host = trim(explode(',', $host)[0]);
  $host = rtrim($host, '/');

  $bp = rtrim(cfg('app')['base_path'] ?? '', '/');
  return $scheme . '://' . $host . $bp;
}

function absolute_url(string $path = ''): string {
  $base = rtrim(app_base_url(), '/');
  $path = ltrim($path, '/');
  return $base . '/' . $path;
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
  $ordered = [];
  foreach (cfg('app')['map_order'] ?? [] as $key) {
    if (isset($dirs[$key])) $ordered[$key] = $dirs[$key];
  }
  return $ordered + $dirs;
}

/** Presentation-only hierarchy, applied after access filtering. */
function map_picker_dirs(array $dirs): array {
  foreach ($dirs as $key => &$dir) $dir['label'] = '📁 ' . ($dir['label'] ?? $key);
  unset($dir);
  $parents = cfg('app')['map_parents'] ?? [];
  $result = [];
  foreach ($dirs as $key => $dir) {
    $parent = $parents[$key] ?? '';
    // A hidden/deleted parent must not hide its readable children or expose its name.
    if ($parent !== '' && isset($dirs[$parent]) && $parent !== (string)$key) continue;
    $result[$key] = $dir;
    foreach ($dirs as $childKey => $child) {
      if (($parents[$childKey] ?? '') !== (string)$key || $childKey === $key) continue;
      $child['label'] = '　└ ' . $child['label'];
      $result[$childKey] = $child;
    }
  }
  return $result + $dirs;
}

/** Null means the installation still uses its legacy content paths. */
function get_content_root(): ?string {
  return cfg('app')['content_root'] ?? null;
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

  // Allow selecting map/folder via query string, e.g. ?map=content
  $requested = trim((string)($_GET['map'] ?? ''));
  if ($requested !== '' && isset($dirs[$requested])) {
    $_SESSION['content_dir_key'] = $requested;
    return $requested;
  }

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

/**
 * True when the current visitor is logged in as any editor account.
 * Always true when the installation has no accounts configured at all
 * (config/auth.php: empty 'users' and empty 'editor_password') — the classic
 * "no password set" open mode.
 */
function is_authed(): bool {
  return App\Auth::isOpen() || App\Auth::currentUser() !== null;
}

/** The logged-in username, or null when not logged in (or when auth is open/disabled). */
function current_user(): ?string {
  return App\Auth::currentUser();
}

/** Human-readable name for a username, falling back to the username itself. */
function user_display_name(?string $username = null): string {
  $username = $username ?? current_user();
  return $username === null ? 'Gäst' : App\Auth::displayName($username);
}

/** Redirects to the login page (preserving the current URL) unless already logged in. */
function require_auth(): void {
  if (is_authed()) return;
  $return = (string)($_SERVER['REQUEST_URI'] ?? '');
  $bp = rtrim(cfg('app')['base_path'] ?? '', '/');
  if ($bp !== '' && str_starts_with($return, $bp)) $return = substr($return, strlen($bp));
  $return = ltrim($return, '/');
  header('Location: ' . base_path('editor/login.php') . ($return !== '' ? '?return=' . rawurlencode($return) : ''));
  exit;
}

/** Whether the current visitor may read (view/export) the given map. */
function can_read_map(string $mapKey): bool {
  if (App\Auth::isOpen()) return true;
  return App\Acl::canRead($mapKey, current_user());
}

/** Whether the current visitor may edit content in the given map. */
function can_edit_map(string $mapKey): bool {
  if (App\Auth::isOpen()) return true;
  return App\Acl::canEdit($mapKey, current_user());
}

/** True for a user who can edit every currently configured map — used to gate cross-map/admin actions. */
function is_admin(): bool {
  foreach (get_content_dirs() as $key => $dir) {
    if (!can_edit_map((string)$key)) return false;
  }
  return true;
}

/** @return array<string,array> Only the maps the current visitor may read. */
function readable_content_dirs(): array {
  return App\Acl::readableMaps(get_content_dirs(), current_user());
}

/** @return array<string,array> Only the maps the current visitor may edit. */
function editable_content_dirs(): array {
  return App\Acl::editableMaps(get_content_dirs(), current_user());
}

function access_denied_html(string $messageHtml): void {
  http_response_code(403);
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!doctype html><meta charset="utf-8"><title>Åtkomst nekad</title>'
    . '<body style="font:15px/1.5 system-ui,sans-serif;max-width:640px;margin:60px auto;padding:0 20px">'
    . '<h1 style="font-size:20px">Åtkomst nekad</h1><p>' . $messageHtml . '</p>'
    . '<p><a href="' . h(base_path('view/index.php')) . '">Till förmågekartan</a></p></body>';
  exit;
}

function access_denied(string $message): void {
  access_denied_html(h($message));
}

/** Ensures the visitor may read $mapKey (defaults to the currently selected map); logs out-visitors in, denies logged-in-but-unpermitted ones. */
function require_read(?string $mapKey = null): void {
  $mapKey = $mapKey ?? get_selected_content_key();
  if (can_read_map($mapKey)) return;
  if (!is_authed()) { require_auth(); return; }
  access_denied('Du har inte behörighet att läsa den här förmågekartan.');
}

/** Ensures the visitor is logged in AND may edit $mapKey (defaults to the currently selected map). */
function require_edit(?string $mapKey = null): void {
  require_auth();
  $mapKey = $mapKey ?? get_selected_content_key();
  if (can_edit_map($mapKey)) return;
  $dirs = get_content_dirs();
  $message = 'Du har inte behörighet att redigera "' . h($dirs[$mapKey]['label'] ?? $mapKey) . '".';
  if (can_read_map($mapKey)) {
    $message .= ' Du kan se kartan i <a href="' . h(base_path('view/overview.php?map=' . rawurlencode($mapKey))) . '">visningsläget</a>.';
  }
  access_denied_html($message);
}

/** Ensures the visitor can edit every configured map — used for cross-map/admin-only actions. */
function require_admin(): void {
  require_auth();
  if (is_admin()) return;
  access_denied('Den här åtgärden kräver behörighet att redigera alla förmågekartor.');
}

spl_autoload_register(function($class){
  $prefix = 'App\\';
  if (str_starts_with($class, $prefix)) {
    $rel = substr($class, strlen($prefix));
    $file = __DIR__ . '/lib/' . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) require $file;
  }
});
