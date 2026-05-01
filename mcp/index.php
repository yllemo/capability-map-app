<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
header('Content-Type: application/json; charset=UTF-8');

use App\PathGuard;

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

function list_capabilities(string $contentDir): array {
  $items = [];
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contentDir, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower($f->getExtension());
    if ($ext !== 'md' && $ext !== 'markdown') continue;
    $relPath = ltrim(str_replace($contentDir, '', $f->getPathname()), DIRECTORY_SEPARATOR);
    $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
    $id = pathinfo($relPath, PATHINFO_FILENAME);
    $items[] = [
      'file' => $relPath,
      'id' => $id,
      'name' => basename($relPath),
      'folder' => dirname($relPath),
    ];
  }
  usort($items, static fn($a, $b) => strcmp($a['file'], $b['file']));
  return $items;
}

function read_capability_markdown(string $contentDir, array $params): ?array {
  $file = trim((string)($params['file'] ?? ''));
  $id = trim((string)($params['id'] ?? ''));

  if ($file === '' && $id === '') return null;

  if ($file === '' && $id !== '') {
    $candidates = [
      $id . '.md',
      $id . '.markdown',
    ];
    $all = list_capabilities($contentDir);
    foreach ($all as $item) {
      if (in_array(basename($item['file']), $candidates, true)) {
        $file = $item['file'];
        break;
      }
    }
  }

  if ($file === '') return null;

  try {
    $abs = PathGuard::safeJoin($contentDir, $file);
  } catch (Throwable $e) {
    return null;
  }
  if (!is_file($abs)) return null;

  return [
    'file' => str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($contentDir, '', $abs), DIRECTORY_SEPARATOR)),
    'id' => pathinfo($abs, PATHINFO_FILENAME),
    'markdown' => (string)file_get_contents($abs),
  ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  json_response([
    'server' => $serverName,
    'description' => 'Local MCP-like endpoint for skills and capabilities',
    'methods' => ['skills/list', 'skills/read', 'capabilities/list', 'capabilities/read', 'tools/list'],
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
$contentDir = get_content_dir();

if ($method === 'tools/list') {
  json_response([
    'tools' => [
      ['name' => 'skills/list', 'description' => 'List available skill documents'],
      ['name' => 'skills/read', 'description' => 'Read one skill document by id'],
      ['name' => 'capabilities/list', 'description' => 'List markdown capability files in current content directory'],
      ['name' => 'capabilities/read', 'description' => 'Read one capability markdown file by file path or id'],
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

if ($method === 'capabilities/list') {
  json_response([
    'capabilities' => list_capabilities($contentDir),
  ]);
}

if ($method === 'capabilities/read') {
  $item = read_capability_markdown($contentDir, $params);
  if ($item === null) {
    json_response(['error' => 'Capability not found'], 404);
  }
  json_response($item);
}

json_response(['error' => 'Unknown method'], 400);
