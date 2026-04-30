<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
header('Content-Type: application/json; charset=UTF-8');

$aiCfg = cfg('ai');
$mcpCfg = $aiCfg['mcp'] ?? [];
$serverName = (string)($mcpCfg['server_name'] ?? 'capability-map-mcp');

function list_skill_entries(array $mcpCfg): array {
  $entries = [];
  $seen = [];

  foreach ((array)($mcpCfg['skills_paths'] ?? []) as $path) {
    $real = realpath((string)$path);
    if (!$real || !is_file($real) || isset($seen[$real])) continue;
    $seen[$real] = true;
    $entries[] = [
      'id' => sha1($real),
      'name' => basename($real),
      'path' => $real,
    ];
  }

  foreach ((array)($mcpCfg['skills_directories'] ?? []) as $dir) {
    $realDir = realpath((string)$dir);
    if (!$realDir || !is_dir($realDir)) continue;
    $it = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($realDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
      if (!$f->isFile()) continue;
      $path = $f->getPathname();
      if (!preg_match('/\.(md|txt|json)$/i', $path)) continue;
      $real = realpath($path);
      if (!$real || isset($seen[$real])) continue;
      $seen[$real] = true;
      $entries[] = [
        'id' => sha1($real),
        'name' => basename($real),
        'path' => $real,
      ];
    }
  }

  return $entries;
}

function json_response(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  json_response([
    'server' => $serverName,
    'description' => 'Local MCP-like endpoint for project skills',
    'methods' => ['skills/list', 'skills/read', 'tools/list'],
  ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
  json_response(['error' => 'Invalid JSON payload'], 400);
}
$method = (string)($payload['method'] ?? '');
$params = (array)($payload['params'] ?? []);
$skills = list_skill_entries($mcpCfg);

if ($method === 'tools/list') {
  json_response([
    'tools' => [
      ['name' => 'skills/list', 'description' => 'List available skill documents'],
      ['name' => 'skills/read', 'description' => 'Read one skill document by id'],
    ],
  ]);
}

if ($method === 'skills/list') {
  $items = array_map(static fn($s) => ['id' => $s['id'], 'name' => $s['name']], $skills);
  json_response(['skills' => array_values($items)]);
}

if ($method === 'skills/read') {
  $id = (string)($params['id'] ?? '');
  foreach ($skills as $s) {
    if ($s['id'] !== $id) continue;
    $content = (string)@file_get_contents($s['path']);
    json_response([
      'id' => $s['id'],
      'name' => $s['name'],
      'content' => $content,
    ]);
  }
  json_response(['error' => 'Skill not found'], 404);
}

json_response(['error' => 'Unknown method'], 400);
