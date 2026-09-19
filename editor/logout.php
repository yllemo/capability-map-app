<?php
require __DIR__ . '/_auth.php';

App\Auth::clearCookie();
header('Location: ' . base_path('editor/login.php'));
exit;
