<?php
require __DIR__ . '/_auth.php';
header('Content-Type: text/html; charset=UTF-8');

use App\Auth;

$isOpen = Auth::isOpen();
$knownUsers = Auth::loginableUsers();
$multiUser = count($knownUsers) > 1 || (count($knownUsers) === 1 && $knownUsers[0] !== 'editor');
$return = ltrim((string)($_GET['return'] ?? $_POST['return'] ?? ''), '/');
// Only allow a plain relative path within this app — never an absolute/external URL.
if (str_contains($return, '://')) $return = '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOpen) {
  $username = trim((string)($_POST['username'] ?? ($multiUser ? '' : 'editor')));
  $password = (string)($_POST['password'] ?? '');
  $verified = Auth::verifyCredentials($username, $password);
  if ($verified !== null) {
    Auth::issueCookie($verified);
    header('Location: ' . ($return !== '' ? base_path(ltrim($return, '/')) : base_path('editor/index.php')));
    exit;
  }
  $error = 'Fel användarnamn eller lösenord';
}

$title = 'Editor login';
$activeNav = 'editor';

$content = '<div class="card" style="max-width:520px;margin:0 auto"><div class="card__hd"><strong>Editor</strong><span class="muted">inlogg</span></div><div class="card__bd">';

if ($isOpen) {
  $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:12px; display:block">';
  $content .= '⚠️ <strong>Inget lösenordsskydd är konfigurerat.</strong> Lägg till konton i <code>/config/auth.php</code> innan driftsättning.';
  $content .= '</div>';
  $content .= '<a class="btn btn--primary" href="' . h(base_path('editor/index.php')) . '">Fortsätt</a>';
} else {
  if (Auth::verifyCredentials('editor', 'CHANGE-ME-TO-SECURE-PASSWORD-BEFORE-DEPLOYMENT') !== null) {
    $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:12px; display:block">';
    $content .= '⚠️ <strong>SÄKERHETSVARNING:</strong> Standardlösenordet är fortfarande aktivt. Ändra det omedelbart i <code>/config/auth.php</code>!';
    $content .= '</div>';
  }
  if ($error) $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:10px">' . h($error) . '</div>';
  $content .= '<form method="post">';
  if ($return !== '') $content .= '<input type="hidden" name="return" value="' . h($return) . '">';
  if ($multiUser) {
    $content .= '<label class="muted" style="display:block;margin-bottom:6px">Användarnamn</label><input class="input" name="username" type="text" autofocus style="margin-bottom:12px">';
  }
  $content .= '<label class="muted" style="display:block;margin-bottom:6px">Lösenord</label><input class="input" name="password" type="password"' . ($multiUser ? '' : ' autofocus') . '>';
  $content .= '<div style="display:flex;gap:10px;margin-top:12px"><button class="btn btn--primary" type="submit">Logga in</button><a class="btn btn--ghost" href="' . h(base_path('view/index.php')) . '">Till viewer</a></div></form>';
}
$content .= '</div></div>';

ob_start();
require __DIR__ . '/../app/templates/layout.php';
echo ob_get_clean();
