<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
header('Content-Type: application/json; charset=UTF-8');

use App\AiClient;
use App\PathGuard;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'CSRF validation failed']);
  exit;
}

$contentDir = get_content_dir();
$file = trim((string)($_POST['file'] ?? ''));
$instruction = trim((string)($_POST['instruction'] ?? ''));
$systemPrompt = trim((string)($_POST['system_prompt'] ?? ''));
$markdown = (string)($_POST['markdown'] ?? '');

if ($file === '' || $instruction === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Missing file or instruction']);
  exit;
}

try {
  $abs = PathGuard::safeJoin($contentDir, $file);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid file path']);
  exit;
}

if (!is_file($abs)) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'error' => 'File not found']);
  exit;
}

$defaultPrompt = (string)(cfg('ai')['default_system_prompt'] ?? '');
$finalPrompt = $systemPrompt !== '' ? $systemPrompt : $defaultPrompt;
$finalPrompt .= "\n\nMCP endpoint: " . absolute_url('mcp/index.php');

$result = AiClient::generateMarkdownEdit($finalPrompt, $instruction, $markdown);
if (!($result['ok'] ?? false)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Unknown AI error']);
  exit;
}

echo json_encode([
  'ok' => true,
  'updated_markdown' => $result['updated_markdown'],
  'notes' => $result['notes'] ?? '',
], JSON_UNESCAPED_UNICODE);
