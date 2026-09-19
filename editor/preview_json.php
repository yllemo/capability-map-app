<?php
require __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=UTF-8');
// A truly fatal error (parse error, OOM, timeout) bypasses try/catch entirely
// and PHP prints it as raw HTML if display_errors is on, which breaks the
// JSON response the client expects. Suppress that raw output and, on the way
// out, replace it with a proper JSON error instead.
ini_set('display_errors', '0');
ob_start();
register_shutdown_function(function () {
  $err = error_get_last();
  if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) return;
  while (ob_get_level() > 0) ob_end_clean();
  error_log('preview_json.php fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
  if (http_response_code() < 400) http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Ett internt fel uppstod vid inläsningen. Försök igen eller kontakta administratören.']);
});
try {
  if (!is_authed()) { http_response_code(401); throw new RuntimeException('Logga in i editorn igen.'); }
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); throw new RuntimeException('Använd POST.'); }
  $token = $_POST['csrf_token'] ?? '';
  if (!is_string($token) || !csrf_verify($token)) { http_response_code(403); throw new RuntimeException('Ladda om sidan och försök igen.'); }
  $file = $_FILES['json_file'] ?? [];
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Välj en JSON-fil inom serverns uppladdningsgräns.');
  if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Filen får vara högst 5 MB.');
  if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Ogiltig uppladdning.');
  $json = file_get_contents($file['tmp_name']);
  if ($json === false) throw new RuntimeException('Kunde inte läsa filen.');
  $prefix = $_POST['id_prefix'] ?? '';
  if (!is_string($prefix)) throw new RuntimeException('ID-prefix måste vara text.');
  $import = App\CapabilityJsonImport::convert($json, $prefix);
  $items = [];
  foreach ($import['files'] as $markdown) $items[] = App\Frontmatter::parse($markdown);
  echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
  if (http_response_code() < 400) http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
