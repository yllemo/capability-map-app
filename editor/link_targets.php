<?php
require __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=UTF-8');
// A fatal error here (e.g. invalid UTF-8 in some capability's frontmatter
// breaking JSON_THROW_ON_ERROR, or an unreadable directory) bypasses
// try/catch and PHP prints raw HTML, which breaks the JSON response the
// client expects. Suppress that and fail with clean JSON instead.
ini_set('display_errors', '0');
ob_start();
register_shutdown_function(function () {
  $err = error_get_last();
  if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
  while (ob_get_level() > 0) ob_end_clean();
  error_log('link_targets.php fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
  if (http_response_code() < 400) http_response_code(500);
  echo json_encode(['error' => 'Ett internt fel uppstod. Försök igen eller kontakta administratören.']);
});

function clean_utf8(string $s): string {
  $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $s);
  return $fixed === false ? '' : $fixed;
}

try {
  if (!is_authed()) {
    http_response_code(401);
    echo json_encode(['error' => 'Logga in i editorn igen.']);
    exit;
  }
  $items = [];
  foreach (readable_content_dirs() as $map => $dir) {
    foreach ((new App\CapabilityRepository($dir['path']))->all() as $cap) {
      $items[] = [
        'map' => clean_utf8((string)$map),
        'mapLabel' => clean_utf8((string)($dir['label'] ?? $map)),
        'id' => clean_utf8($cap->id),
        'name' => clean_utf8($cap->name),
      ];
    }
  }
  echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
  error_log('link_targets.php error: ' . $e->getMessage());
  if (http_response_code() < 400) http_response_code(500);
  echo json_encode(['error' => 'Kunde inte hämta förmågor.']);
}
