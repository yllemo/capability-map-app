<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/Auth.php';
$authConfig = ['users'=>['admin'=>['password_hash'=>password_hash('test-password-123', PASSWORD_DEFAULT)], 'anna'=>['role'=>'editor']], 'session_secret'=>'test-only-secret'];
function cfg(string $name): array { global $authConfig; return $authConfig; }
function base_path(string $path = ''): string { return '/' . ltrim($path, '/'); }
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function resetAuth(): void {
  $property = new ReflectionProperty(App\Auth::class, 'resolved');
  $property->setAccessible(true);
  $property->setValue(null, null);
}
function loginCookie(string $user, string $version = ''): void {
  $expires = time() + 3600;
  $_COOKIE['capmap_editor'] = $user . '|' . $expires . '|' . hash_hmac('sha256', $user . '|' . $expires . $version, 'test-only-secret');
  resetAuth();
}
check(!App\Auth::isAdministrator(), 'Guests cannot administer.');
loginCookie('admin');
check(App\Auth::isAdministrator(), 'Existing administrator retains access.');
loginCookie('anna');
check(!App\Auth::isAdministrator(), 'Editors cannot administer.');
$authConfig['users']['anna']['role'] = 'admin';
check(App\Auth::isAdministrator(), 'Explicit administrators have access.');
$authConfig['users']['anna']['session_version'] = 'new-version';
resetAuth();
check(App\Auth::currentUser() === null, 'Old sessions are revoked after account changes.');
loginCookie('anna', 'new-version');
check(App\Auth::isAdministrator(), 'New sessions are valid.');
unset($authConfig['users']['anna']);
resetAuth();
check(App\Auth::currentUser() === null, 'Deleted users cannot retain access.');
$authConfig['users'] = [];
resetAuth();
check(!App\Auth::isAdministrator(), 'Open installations do not grant anonymous administration.');
echo "Admin authentication tests passed\n";
