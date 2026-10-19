<?php
require __DIR__ . '/app/bootstrap.php';

$default = cfg('app')['default_mode'] ?? 'view';
$query = $_SERVER['QUERY_STRING'] ?? '';
$querySuffix = $query !== '' ? ('?' . $query) : '';
$target = ($default === 'editor' ? 'editor/index.php' : 'view/index.php') . $querySuffix;

if ((cfg('app')['base_path'] ?? '') === '') {
  // Only the browser knows the public mount point when a proxy strips it.
  header('Content-Type: text/html; charset=UTF-8');
  ?><!doctype html><html lang="sv"><meta charset="utf-8"><title>Förmågekarta</title>
  <script>
    const root = new URL(window.location.href);
    root.pathname = root.pathname.replace(/\/index\.php$/, '/').replace(/\/?$/, '/');
    root.search = ''; root.hash = '';
    window.location.replace(new URL(<?= json_encode($target, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, root).href);
  </script>
  <noscript><p>Öppna appens adress med avslutande snedstreck och följ länken till <a href="<?= h(base_path($target)) ?>">förmågekartan</a>.</p></noscript></html><?php
  exit;
}

if ($default === 'editor') {
  header('Location: ' . base_path('editor/index.php') . $querySuffix);
  exit;
}
header('Location: ' . base_path('view/index.php') . $querySuffix);
exit;
