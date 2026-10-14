<?php
declare(strict_types=1);

function pathCheck(bool $ok, string $message): void {
  if (!$ok) throw new RuntimeException($message);
}

putenv('APP_BASE_PATH=/ignored');
require __DIR__ . '/../app/bootstrap.php';
$prefix = normalize_base_path((string)((require __DIR__ . '/../config/app.php')['base_path'] ?? ''));
pathCheck(cfg('app')['base_path'] === $prefix, 'Code configuration controls prefix, independent of environment');
foreach ([''=>'', '/'=>'', 'cap'=>'/cap', '/cap/'=>'/cap', ' /team/cap/ '=>'/team/cap', '/cap.v1'=>'/cap.v1'] as $input=>$expected) {
  pathCheck(normalize_base_path($input) === $expected, 'Prefix normalization: ' . $input);
}
foreach (['https://example.test/cap', '/cap?x=1', '/cap/../other', '//other.test', '/cap#fragment'] as $input) {
  try {
    normalize_base_path($input);
    throw new LogicException('Invalid prefix accepted: ' . $input);
  } catch (RuntimeException $e) {}
}

pathCheck(base_path('assets/overview.css') === $prefix . '/assets/overview.css', 'CSS prefix');
pathCheck(base_path('/assets/editor.js') === $prefix . '/assets/editor.js', 'JavaScript prefix');
pathCheck(base_path('/') === $prefix . '/', 'Root and cookie prefix');
pathCheck(base_path('editor/index.php?map=content') === $prefix . '/editor/index.php?map=content', 'Query preservation');
pathCheck(ini_get('session.cookie_path') === $prefix . '/', 'Session cookie isolation');
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['HTTP_X_FORWARDED_HOST'] = 'apps.example.test';
pathCheck(absolute_url('mcp/index.php') === 'https://apps.example.test' . $prefix . '/mcp/index.php', 'Public URL behind proxy');
session_write_close();
echo "Base path tests passed\n";
