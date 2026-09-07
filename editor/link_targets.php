<?php
require __DIR__ . '/_auth.php';
header('Content-Type: application/json; charset=UTF-8');
if (!is_authed()) {
  http_response_code(401);
  echo json_encode(['error' => 'Logga in i editorn igen.']);
  exit;
}
$items = [];
foreach (get_content_dirs() as $map => $dir) {
  foreach ((new App\CapabilityRepository($dir['path']))->all() as $cap) {
    $items[] = ['map' => (string)$map, 'mapLabel' => $dir['label'] ?? $map, 'id' => $cap->id, 'name' => $cap->name];
  }
}
echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
