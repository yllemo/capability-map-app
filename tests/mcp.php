<?php
declare(strict_types=1);
foreach (['Frontmatter','Capability','CapabilityReference','CapabilityRepository','McpTools','McpServer'] as $class) require __DIR__ . '/../app/lib/' . $class . '.php';
function check(bool $ok, string $why): void { if (!$ok) throw new RuntimeException($why); }
$root = sys_get_temp_dir() . '/capmap-mcp-' . bin2hex(random_bytes(8));
mkdir($root);
file_put_contents($root . '/different-name.md', "---\nid: cap-real-id\nname: Styrning\ntags:\n  - styrning\n---\nBody");
try {
  $tools = new App\McpTools(['test'=>['path'=>$root,'label'=>'Test']], [], false);
  $server = new App\McpServer($tools);
  $meta = ['io.modelcontextprotocol/protocolVersion'=>App\McpServer::VERSION,'io.modelcontextprotocol/clientCapabilities'=>(object)[]];
  $call = static function(string $method, array $params = [], array $overrides = []) use ($server,$meta): array {
    $headers = array_replace(['mcp-protocol-version'=>App\McpServer::VERSION,'mcp-method'=>$method,'mcp-name'=>$params['name'] ?? ''],$overrides);
    return $server->handle(json_encode(['jsonrpc'=>'2.0','id'=>7,'method'=>$method,'params'=>$params+['_meta'=>$meta]]),$headers);
  };
  [$status,$response] = $call('server/discover');
  check($status===200 && $response['result']->resultType==='complete','Discovery');
  check($response['id']===7,'Preserve RPC ID');
  [$status,$response] = $call('tools/list');
  check(count($response['result']->tools)===5,'Tool definitions');
  foreach ($response['result']->tools as $tool) check($tool['inputSchema']['properties'] instanceof stdClass,'Schema properties are objects');
  [$status,$response] = $call('tools/call',['name'=>'capabilities_read','arguments'=>(object)['map'=>'test','id'=>'cap-real-id']]);
  check($response['result']->structuredContent['file']==='different-name.md','Lookup by YAML ID, not filename');
  [$status,$response] = $call('tools/call',['name'=>'capabilities_list','arguments'=>(object)['tag'=>'STYRNING','limit'=>1]]);
  check($response['result']->structuredContent['total']===1,'Case-insensitive tag search');
  [$status,$response] = $call('tools/call',['name'=>'capabilities_read','arguments'=>(object)['map'=>'secret','id'=>'cap-real-id']]);
  check($response['result']->isError===true,'Map ACL');
  [$status,$response] = $call('tools/call',['name'=>'capabilities_read','arguments'=>(object)['map'=>'test','file'=>'../config/auth.php']]);
  check($response['result']->isError===true,'Traversal rejected');
  [$status,$response] = $call('tools/call',['name'=>'skills_list']);
  check($response['result']->isError===true,'Anonymous skills denied');
  [$status,$response] = $call('tools/call',['name'=>'capabilities_list','arguments'=>(object)['limit'=>101]]);
  check($response['error']['code']===-32602,'Argument schema enforced');
  [$status,$response] = $call('tools/list',[],['mcp-method'=>'tools/call']);
  check($response['error']['code']===-32020,'Header/body mismatch');
  [$status,$response] = $call('tools/list',['_meta'=>(object)[]]);
  check($response['error']['code']===-32602,'Required modern metadata');
  [$status,$response] = $call('tools/call',['name'=>'missing_tool']);
  check($response['error']['code']===-32602,'Unknown tool');
  [$status,$response] = $call('tools/call',['name'=>'maps_list','arguments'=>[]]);
  check($response['error']['code']===-32602,'Arguments must be an object');
  [$status,$response] = $call('tools/list',['_meta'=>['io.modelcontextprotocol/protocolVersion'=>'1900-01-01','io.modelcontextprotocol/clientCapabilities'=>(object)[]]],['mcp-protocol-version'=>'1900-01-01']);
  check($response['error']['code']===-32022,'Unsupported version');
  [$status,$response] = $call('unknown'); check($status===404 && $response['error']['code']===-32601,'Unknown method');
  check($server->handle('{',[])[1]['error']['code']===-32700,'Parse error');
  check($server->handle('[]',[])[1]['error']['code']===-32600,'Batches rejected');
  [$status,$response] = $server->handle(json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>'initialize','params'=>['protocolVersion'=>'2025-11-25','capabilities'=>(object)[],'clientInfo'=>(object)['name'=>'test','version'=>'1']]]),[]);
  check($response['result']->protocolVersion==='2025-11-25','Legacy initialization');
  check($server->handle('{"jsonrpc":"2.0","method":"notifications/initialized"}',['mcp-protocol-version'=>'2025-11-25'])===[202,null],'Empty notification response');
} finally { unlink($root . '/different-name.md'); rmdir($root); }
echo "MCP tests passed\n";
