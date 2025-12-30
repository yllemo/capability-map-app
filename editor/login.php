<?php
require __DIR__ . '/_auth.php';
header('Content-Type: text/html; charset=UTF-8');

$auth = cfg('auth');
$cookie = $auth['cookie_name'] ?? 'capmap_editor';
$ttl = $auth['cookie_ttl'] ?? 28800;
$pass = $auth['editor_password'] ?? '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $p = (string)($_POST['password'] ?? '');
  if ($pass === '' || hash_equals($pass, $p)) {
    setcookie($cookie, $pass, [
      'expires' => time() + (int)$ttl,
      'path' => base_path('/'),
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    header('Location: ' . base_path('editor/index.php'));
    exit;
  }
  $error = 'Fel lösenord';
}

$title = 'Editor login';
$activeNav = 'editor';

$content = '<div class="card" style="max-width:520px;margin:0 auto"><div class="card__hd"><strong>Editor</strong><span class="muted">inlogg</span></div><div class="card__bd">';

// Warn if using default password
if ($pass === 'change-me') {
  $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:12px; display:block">';
  $content .= '⚠️ <strong>SÄKERHETSVARNING:</strong> Du använder standardlösenordet "change-me". Ändra omedelbart i <code>/config/auth.php</code>!';
  $content .= '</div>';
}

if ($pass === '') {
  $content .= '<p class="muted">Editor-lösenord är avstängt i <code>/config/auth.php</code>.</p>';
  $content .= '<a class="btn btn--primary" href="index.php">Fortsätt</a>';
} else {
  if ($error) $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:10px">'.h($error).'</div>';
  $content .= '<form method="post"><label class="muted" style="display:block;margin-bottom:6px">Lösenord</label><input class="input" name="password" type="password" autofocus>';
  $content .= '<div style="display:flex;gap:10px;margin-top:12px"><button class="btn btn--primary" type="submit">Logga in</button><a class="btn btn--ghost" href="'.h(base_path('view/index.php')).'">Till viewer</a></div></form>';
}
$content .= '</div></div>';

ob_start();
require __DIR__ . '/../app/templates/layout.php';
echo ob_get_clean();
