<?php
require __DIR__ . '/../app/bootstrap.php';

$auth = cfg('auth');
$cookie = $auth['cookie_name'] ?? 'capmap_editor';
$pass = $auth['editor_password'] ?? '';

function is_authed(): bool {
  $auth = cfg('auth');
  $cookie = $auth['cookie_name'] ?? 'capmap_editor';
  $pass = $auth['editor_password'] ?? '';
  if ($pass === '') return true;
  return isset($_COOKIE[$cookie]) && hash_equals($pass, (string)$_COOKIE[$cookie]);
}

function require_auth(): void {
  if (!is_authed()) {
    header('Location: ' . base_path('editor/login.php'));
    exit;
  }
}
