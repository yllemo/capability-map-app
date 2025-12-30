<?php
require __DIR__ . '/_auth.php';

$auth = cfg('auth');
$cookie = $auth['cookie_name'] ?? 'capmap_editor';
setcookie($cookie, '', time()-3600, base_path('/'));
header('Location: ' . base_path('editor/login.php'));
exit;
