<?php
require __DIR__ . '/../app/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

use App\Markdown;

$md = (string)($_POST['md'] ?? '');
echo Markdown::toHtml($md);
