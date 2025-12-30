<?php
require __DIR__ . '/_auth.php';
require_auth();

use App\PathGuard;

$app = cfg('app');

// CSRF protection
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo 'CSRF validation failed';
  exit;
}

// Use selected content directory
$contentDir = get_content_dir();

$rel = (string)($_POST['file'] ?? '');
if ($rel === '') { http_response_code(400); echo 'Missing file'; exit; }

try {
  $abs = PathGuard::safeJoin($contentDir, $rel);
} catch (Throwable $e) {
  http_response_code(400); echo 'Invalid path'; exit;
}

$id = trim((string)($_POST['id'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$layer = trim((string)($_POST['layer'] ?? ''));
$area = trim((string)($_POST['area'] ?? ''));
$level = (int)($_POST['level'] ?? 0);
$type = trim((string)($_POST['type'] ?? ''));
$owner = trim((string)($_POST['owner'] ?? ''));
$status = trim((string)($_POST['status'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$maturity = (int)($_POST['maturity'] ?? 0);
$criticality = (int)($_POST['criticality'] ?? 0);
$body = (string)($_POST['body'] ?? '');

if ($id === '' || $name === '') {
  header('Location: index.php?file=' . rawurlencode($rel) . '&error=missing_fields');
  exit;
}

// Check for duplicate ID (only if ID changed)
use App\CapabilityRepository;
use App\Frontmatter;
$repo = new CapabilityRepository($contentDir);
$raw = file_exists($abs) ? (string)file_get_contents($abs) : '';
$parsed = Frontmatter::parse($raw);
$oldId = $parsed['meta']['id'] ?? '';

if ($id !== $oldId) {
  $existing = $repo->byId($id);
  if ($existing) {
    header('Location: index.php?file=' . rawurlencode($rel) . '&error=duplicate_id');
    exit;
  }
}

$updated = date('Y-m-d');

$yaml = "---\n";
$yaml .= "id: " . $id . "\n";
$yaml .= "name: " . str_replace("\n", ' ', $name) . "\n";
$yaml .= "layer: " . $layer . "\n";
$yaml .= "area: " . str_replace("\n", ' ', $area) . "\n";
$yaml .= "level: " . $level . "\n";
$yaml .= "type: " . $type . "\n";
$yaml .= "description: " . str_replace("\n", ' ', $description) . "\n";
$yaml .= "owner: " . str_replace("\n", ' ', $owner) . "\n";
$yaml .= "status: " . str_replace("\n", ' ', $status) . "\n";
$yaml .= "maturity: " . $maturity . "\n";
$yaml .= "criticality: " . $criticality . "\n";
$yaml .= "updated: " . $updated . "\n";
$yaml .= "---\n\n";

$content = $yaml . $body;

// Ensure UTF-8 encoding without BOM
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

@mkdir(dirname($abs), 0775, true);
file_put_contents($abs, $content, LOCK_EX);

// Log the save action
use App\Logger;
Logger::audit('capability_saved', ['file' => $rel, 'id' => $id]);

header('Location: index.php?file=' . rawurlencode($rel) . '&success=saved');
exit;
