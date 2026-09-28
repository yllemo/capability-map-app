<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
$title = 'MCP-test'; $activeNav = 'ai';
ob_start();
?>
<h1>MCP-test</h1>
<p>Testar riktig JSON-RPC via Streamable HTTP. Ange webbplatsens origin i MCP_ALLOWED_ORIGINS. Verktygen läser endast förmågor.</p>
<div class="card"><div class="card__bd">
<label>Metod <select id="rpc-method" class="select"><option>server/discover</option><option>tools/list</option><option>tools/call</option><option>ping</option></select></label>
<label>Verktyg <select id="rpc-tool" class="select"><?php foreach (App\McpTools::definitions() as $tool): ?><option><?= h($tool['name']) ?></option><?php endforeach; ?></select></label>
<label for="rpc-args">Argument (JSON)</label><textarea id="rpc-args" class="textarea" rows="5" style="width:100%">{}</textarea>
<p>Exempel: <code>{"map":"content","id":"cap-1"}</code> för capabilities_read. maps_list visar tillgängliga kartnycklar.</p>
<button type="button" class="btn btn--primary" id="rpc-run">Kör anrop</button>
<pre id="rpc-output" style="white-space:pre-wrap;overflow-wrap:anywhere" role="status"></pre>
</div></div>
<script>
document.getElementById('rpc-run').addEventListener('click', async () => {
  const output = document.getElementById('rpc-output');
  try {
    const method = document.getElementById('rpc-method').value;
    const params = {_meta:{'io.modelcontextprotocol/protocolVersion':'2026-07-28','io.modelcontextprotocol/clientCapabilities':{},'io.modelcontextprotocol/clientInfo':{name:'capmap-test',version:'2.0.0'}}};
    const headers = {'Content-Type':'application/json','Accept':'application/json, text/event-stream','MCP-Protocol-Version':'2026-07-28','Mcp-Method':method};
    if (method === 'tools/call') {
      params.name = document.getElementById('rpc-tool').value;
      params.arguments = JSON.parse(document.getElementById('rpc-args').value);
      headers['Mcp-Name'] = params.name;
    }
    const response = await fetch(<?= json_encode(base_path('mcp/index.php')) ?>,{method:'POST',headers,body:JSON.stringify({jsonrpc:'2.0',id:1,method,params})});
    output.textContent = 'HTTP ' + response.status + '\n' + JSON.stringify(await response.json(),null,2);
  } catch (error) { output.textContent = error.message; }
});
</script>
<?php $content = ob_get_clean(); require __DIR__ . '/../app/templates/layout.php'; ?>
