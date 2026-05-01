<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');

$mcpUrl = base_path('mcp/index.php');
$mcpAbsoluteUrl = absolute_url('mcp/index.php');
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MCP Test</title>
  <link rel="icon" href="<?= h(base_path('assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= h(base_path('assets/app.css')) ?>">
  <style>
    body { padding: 20px; max-width: 1100px; margin: 0 auto; }
    .row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
    pre { max-height: 55vh; overflow: auto; margin: 0; }
    .muted { font-size: 12px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="card__hd">
      <strong>MCP Testverktyg</strong>
      <a class="btn btn--ghost" href="<?= h(base_path('ai/index.php')) ?>">Till AI</a>
    </div>
    <div class="card__bd">
      <p class="muted">
        Endpoint (relativ): <code><?= h($mcpUrl) ?></code><br>
        Endpoint (absolut): <code><?= h($mcpAbsoluteUrl) ?></code>
      </p>

      <div class="row">
        <button class="btn btn--secondary" id="btnGet">GET info</button>
        <button class="btn btn--secondary" id="btnTools">POST tools/list</button>
        <button class="btn btn--secondary" id="btnSkills">POST skills/list</button>
        <button class="btn btn--secondary" id="btnReadFirst">POST skills/read (första)</button>
        <button class="btn btn--secondary" id="btnCaps">POST capabilities/list</button>
        <button class="btn btn--secondary" id="btnCapReadFirst">POST capabilities/read (första)</button>
        <button class="btn btn--ghost" id="btnClear">Rensa</button>
      </div>

      <div class="card">
        <div class="card__hd"><strong>Resultat</strong></div>
        <div class="card__bd">
          <pre id="output" class="prose">Klicka på ett test.</pre>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const mcpUrl = <?= json_encode($mcpUrl) ?>;
      const output = document.getElementById('output');

      function print(title, data) {
        const text = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
        output.textContent = `[${new Date().toLocaleTimeString()}] ${title}\n${text}\n\n` + output.textContent;
      }

      async function callGet() {
        const res = await fetch(mcpUrl, { method: 'GET' });
        const text = await res.text();
        let parsed = text;
        try { parsed = JSON.parse(text); } catch (e) {}
        print(`GET ${mcpUrl} (${res.status})`, parsed);
        return parsed;
      }

      async function callPost(method, params) {
        const res = await fetch(mcpUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ method, params: params || {} })
        });
        const text = await res.text();
        let parsed = text;
        try { parsed = JSON.parse(text); } catch (e) {}
        print(`POST ${method} (${res.status})`, parsed);
        return parsed;
      }

      document.getElementById('btnGet').addEventListener('click', async () => {
        try { await callGet(); } catch (e) { print('GET error', String(e)); }
      });

      document.getElementById('btnTools').addEventListener('click', async () => {
        try { await callPost('tools/list'); } catch (e) { print('tools/list error', String(e)); }
      });

      document.getElementById('btnSkills').addEventListener('click', async () => {
        try { await callPost('skills/list'); } catch (e) { print('skills/list error', String(e)); }
      });

      document.getElementById('btnReadFirst').addEventListener('click', async () => {
        try {
          const list = await callPost('skills/list');
          const firstId = list && list.skills && list.skills[0] && list.skills[0].id;
          if (!firstId) {
            print('skills/read', 'Inga skills hittades att läsa.');
            return;
          }
          await callPost('skills/read', { id: firstId });
        } catch (e) {
          print('skills/read error', String(e));
        }
      });

      document.getElementById('btnCaps').addEventListener('click', async () => {
        try { await callPost('capabilities/list'); } catch (e) { print('capabilities/list error', String(e)); }
      });

      document.getElementById('btnCapReadFirst').addEventListener('click', async () => {
        try {
          const list = await callPost('capabilities/list');
          const firstFile = list && list.capabilities && list.capabilities[0] && list.capabilities[0].file;
          if (!firstFile) {
            print('capabilities/read', 'Inga förmågor hittades att läsa.');
            return;
          }
          await callPost('capabilities/read', { file: firstFile });
        } catch (e) {
          print('capabilities/read error', String(e));
        }
      });

      document.getElementById('btnClear').addEventListener('click', () => {
        output.textContent = 'Klicka på ett test.';
      });
    })();
  </script>
</body>
</html>
