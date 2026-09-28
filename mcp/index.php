<?php
declare(strict_types=1);
require __DIR__ . '/../editor/_auth.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function mcp_reply(array $response): never {
  [$status,$body] = $response; http_response_code($status);
  if ($body !== null) echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}
$config = cfg('mcp');
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin !== null && !in_array($origin, $config['allowed_origins'] ?? [], true)) mcp_reply(App\McpServer::error(null,-32600,'Origin not allowed',403));
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Allow: POST'); mcp_reply(App\McpServer::error(null,-32600,'Use POST for MCP requests',405));
}
if (strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') mcp_reply(App\McpServer::error(null,-32600,'Content-Type must be application/json',415));
$accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
if (!str_contains($accept,'application/json') || !str_contains($accept,'text/event-stream')) mcp_reply(App\McpServer::error(null,-32600,'Accept must include application/json and text/event-stream',406));
$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($authorization !== '') {
  $user = null;
  if (preg_match('/^Bearer (\S+)$/iD',$authorization,$match)) {
    $hash = (string)($config['token_sha256'] ?? '');
    if ($hash !== '' && hash_equals($hash,hash('sha256',$match[1]))) $user = $config['token_user'] ?? null;
  }
  if (!is_string($user) || !in_array($user,App\Auth::loginableUsers(),true)) {
    header('WWW-Authenticate: Bearer realm="capability-map-mcp"');
    mcp_reply(App\McpServer::error(null,-32600,'Invalid MCP credentials',401));
  }
  App\Auth::useRequestIdentity($user);
}
if (current_user() === null && !($config['allow_anonymous'] ?? false)) {
  header('WWW-Authenticate: Bearer realm="capability-map-mcp"');
  mcp_reply(App\McpServer::error(null,-32600,'Authentication required',401));
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$input = file_get_contents('php://input',false,null,0,1048577);
if ($input === false || strlen($input)>1048576) mcp_reply(App\McpServer::error(null,-32600,'Request exceeds 1 MB',413));
$headers = [];
foreach (['mcp-protocol-version','mcp-method','mcp-name'] as $key) $headers[$key] = $_SERVER['HTTP_' . strtoupper(str_replace('-','_',$key))] ?? '';
$tools = new App\McpTools(readable_content_dirs(),cfg('ai')['mcp'] ?? [],current_user() !== null);
$server = new App\McpServer($tools,(string)(cfg('ai')['mcp']['server_name'] ?? 'capability-map-mcp'));
mcp_reply($server->handle($input,$headers));
