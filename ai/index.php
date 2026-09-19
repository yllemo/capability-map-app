<?php
require __DIR__ . '/../editor/_auth.php';
require_edit();
header('Content-Type: text/html; charset=UTF-8');

use App\Frontmatter;

$tax = cfg('taxonomy');
$selectedKey = get_selected_content_key();
$contentDir = get_content_dir();
$aiCfg = cfg('ai');
$systemPrompt = (string)($aiCfg['default_system_prompt'] ?? '');
$rel = trim((string)($_GET['file'] ?? ''));
$notice = isset($_GET['saved']) ? 'Filen sparades' : '';

// Collect every markdown file with its frontmatter, so the sidebar can show
// a readable label and the search box can filter by name/ID/area.
$files = [];
$fileMeta = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contentDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
  if (!$f->isFile()) continue;
  $ext = strtolower($f->getExtension());
  if ($ext !== 'md' && $ext !== 'markdown') continue;
  $relPath = ltrim(str_replace($contentDir, '', $f->getPathname()), DIRECTORY_SEPARATOR);
  if (preg_match('~(^|[/\\\\])\.capmap-import-[^/\\\\]+([/\\\\]|$)~', $relPath)) continue;
  $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
  $files[] = $relPath;
  $fm = [];
  $fRaw = file_get_contents($f->getPathname());
  if ($fRaw !== false) {
    $fParsed = Frontmatter::parse($fRaw);
    if (is_array($fParsed['meta'] ?? null)) $fm = $fParsed['meta'];
  }
  $fileMeta[$relPath] = $fm;
}
sort($files);
if ($rel === '' && !empty($files)) $rel = $files[0];

$currentMeta = $fileMeta[$rel] ?? [];
$currentId = is_string($currentMeta['id'] ?? null) ? $currentMeta['id'] : pathinfo($rel, PATHINFO_FILENAME);
$currentName = is_string($currentMeta['name'] ?? null) ? $currentMeta['name'] : '';

$mapQuery = '?map=' . rawurlencode($selectedKey);
$backTarget = base_path('view/overview.php' . $mapQuery);
$mcpUrl = absolute_url('mcp/index.php');
$mcpPath = base_path('mcp/index.php');
$chatUrl = base_path('ai/chat.php');
$saveUrl = base_path('ai/save.php');
?><!doctype html>
<html lang="sv">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AI Editor · Förmågekarta</title>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/editor.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
  <script defer src="<?= h(base_path('assets/editor.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#ai-main">Hoppa till AI-editorn</a>
<header class="site-header">
  <a class="brand" href="<?= h($backTarget) ?>" style="text-decoration:none;color:inherit" title="Tillbaka till förmågekartan">
    <span class="brand-symbol" aria-hidden="true">▦</span>
    <div><strong>Förmågekarta</strong><span>AI Editor</span></div>
  </a>
  <nav aria-label="Verktyg">
    <a href="<?= h($backTarget) ?>">← Karta</a>
    <a href="<?= h(base_path('view/index.php')) ?>">Viewer</a>
    <?php if ($rel !== ''): ?><a href="<?= h(base_path('editor/index.php?file=' . rawurlencode($rel) . '&map=' . rawurlencode($selectedKey))) ?>">Vanlig editor</a><?php endif; ?>
    <?php if (current_user() !== null): ?>
      <span class="header-user" title="Inloggad som <?= h(user_display_name()) ?>"><?= h(user_display_name()) ?></span>
      <a href="<?= h(base_path('editor/logout.php')) ?>">Logga ut</a>
    <?php endif; ?>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main id="ai-main">
  <?php if ($notice): ?>
    <div class="editor-notice is-ok" role="status"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="editor-layout">
    <aside class="editor-sidebar" aria-label="Förmågor">
      <div class="editor-sidebar-head">
        <div class="editor-sidebar-title">
          <strong>Förmågor</strong>
          <span class="editor-file-count" id="editor-file-count"><?= count($files) ?> filer</span>
        </div>
        <label class="search-label" for="editor-search">Sök förmåga<input id="editor-search" type="search" placeholder="Namn, ID, område eller fil…" autocomplete="off"></label>
      </div>
      <p id="editor-file-empty" class="editor-file-empty" hidden>Inga träffar. Prova ett annat sökord.</p>
      <div class="editor-file-list" id="editor-file-list">
        <?php foreach ($files as $f):
          $fm = $fileMeta[$f];
          $isRedirect = isset($fm['redirect_map']);
          $fName = is_string($fm['name'] ?? null) ? $fm['name'] : '';
          $fId = is_string($fm['id'] ?? null) ? $fm['id'] : '';
          $fLayer = is_string($fm['layer'] ?? null) ? $fm['layer'] : '';
          $fArea = is_string($fm['area'] ?? null) ? $fm['area'] : '';
          $active = ($f === $rel);
          $searchText = mb_strtolower($f . ' ' . $fName . ' ' . $fId . ' ' . $fArea, 'UTF-8');
          $layerLabel = $fLayer !== '' ? ($tax['layer_display_names'][$fLayer] ?? $tax['layers'][$fLayer] ?? $fLayer) : '';
          $fileHref = 'index.php?file=' . rawurlencode($f) . '&map=' . rawurlencode($selectedKey);
        ?>
        <a class="editor-file<?= $active ? ' is-active' : '' ?>" href="<?= h($fileHref) ?>" data-search="<?= h($searchText) ?>">
          <span class="editor-file-name"><?= h($fName !== '' ? $fName : basename($f)) ?></span>
          <span class="editor-file-path"><?= h($f) ?></span>
          <?php if ($fId !== ''): ?><span class="editor-file-id"><?= h($fId) ?></span><?php endif; ?>
          <?php if ($isRedirect): ?><span class="editor-file-flag">↗ länkad förmåga</span><?php elseif ($layerLabel !== ''): ?><span class="editor-file-flag"><?= h($layerLabel) ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
      <div class="editor-sidebar-foot">
        <a class="btn btn--ghost" href="<?= h(base_path('editor/index.php' . $mapQuery)) ?>">Öppna vanlig editor</a>
      </div>
    </aside>

    <section class="editor-main" aria-label="AI-redigering">
      <?php if ($rel === ''): ?>
        <div class="editor-empty">
          <p class="eyebrow">AI EDITOR</p>
          <h1>Ingen markdown-fil hittades</h1>
          <p>Den valda kartan innehåller inga filer att redigera med AI.</p>
        </div>
      <?php else: ?>
        <form id="aiSaveForm" method="post" action="<?= h($saveUrl) ?>">
          <input type="hidden" name="file" value="<?= h($rel) ?>">
          <?= csrf_field() ?>
          <input type="hidden" id="markdownInput" name="markdown">

          <div class="editor-main-head">
            <div>
              <p class="eyebrow">AI-REDIGERAR<?php if ($currentId !== ''): ?> <span><?= h($currentId) ?></span><?php endif; ?></p>
              <h1><?= h($currentName !== '' ? $currentName : basename($rel, '.md')) ?></h1>
              <p class="editor-file-sub">Fil: <code><?= h($rel) ?></code></p>
            </div>
            <div class="editor-main-actions">
              <button type="button" class="btn btn--secondary" id="runAiBtn">✨ Kör AI</button>
              <button type="submit" class="btn btn--primary">💾 Spara fil</button>
              <a class="btn btn--ghost" href="<?= h(base_path('view/capability_new.php?id=' . rawurlencode($currentId) . '&map=' . rawurlencode($selectedKey))) ?>">Öppna i viewer</a>
            </div>
          </div>

          <div id="aiStatusRow" style="margin-bottom:18px">
            <span id="aiWorkingIndicator" class="ai-status" style="display:none">AI arbetar…</span>
          </div>

          <fieldset class="editor-group">
            <legend>Instruktion</legend>
            <details class="ai-details">
              <summary>Visa/redigera systemprompt</summary>
              <textarea id="systemPrompt" class="textarea" style="height:90px"><?= h($systemPrompt) ?></textarea>
            </details>
            <div class="editor-field">
              <label for="instruction">Instruktion till AI</label>
              <textarea id="instruction" class="textarea" style="height:64px;min-height:56px;max-height:160px;resize:vertical;width:100%" placeholder="Ex: Förtydliga beskrivning och förbättra tags"></textarea>
            </div>
          </fieldset>

          <fieldset class="editor-group editor-group--content">
            <legend>Innehåll</legend>
            <div class="editor-content-split">
              <div class="editor-content-col">
                <span class="editor-content-label">Markdown (redigerbar)</span>
                <textarea id="markdownEditor" class="textarea ai-markdown"></textarea>
              </div>
              <div class="editor-content-col">
                <span class="editor-content-label">AI-respons</span>
                <div id="aiNotes" class="editor-preview ai-response">
                  <div class="prose" style="max-width:none"><p class="muted">Ingen AI-körning ännu.</p></div>
                </div>
              </div>
            </div>
          </fieldset>

          <div class="editor-footer-actions">
            <span class="ai-mcp-note">MCP-endpoint: <code><?= h($mcpUrl) ?></code></span>
          </div>
        </form>

        <script>
        (function(){
          const runBtn = document.getElementById("runAiBtn");
          const notes = document.getElementById("aiNotes");
          const instructionEl = document.getElementById("instruction");
          const systemPromptEl = document.getElementById("systemPrompt");
          const markdownEl = document.getElementById("markdownEditor");
          const markdownInput = document.getElementById("markdownInput");
          const saveForm = document.getElementById("aiSaveForm");
          const workingIndicator = document.getElementById("aiWorkingIndicator");
          const mcpPath = <?= json_encode($mcpPath) ?>;
          const file = <?= json_encode($rel) ?>;
          const csrfToken = <?= json_encode(csrf_token()) ?>;
          const chatUrl = <?= json_encode($chatUrl) ?>;

          function setNotes(html){
            notes.innerHTML = "<div class=\"prose\" style=\"max-width:none\">" + html + "</div>";
          }

          async function mcpCall(method, params){
            const res = await fetch(mcpPath, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ method, params: params || {} }),
            });
            const data = await res.json();
            if(!res.ok || data.error){
              throw new Error(data.error || ("MCP fel (" + res.status + ")"));
            }
            return data;
          }

          async function loadMarkdownFromMcp(){
            if(!file) return;
            try {
              const data = await mcpCall("capabilities/read", { file });
              if (typeof data.markdown === "string") {
                markdownEl.value = data.markdown;
                markdownInput.value = data.markdown;
              }
            } catch(err){
              setNotes("<p style=\"color:var(--danger)\">Kunde inte läsa markdown via MCP: " + String(err.message || err).replace(/</g, "&lt;") + "</p>");
            }
          }

          saveForm.addEventListener("submit", function(){
            markdownInput.value = markdownEl.value;
          });

          loadMarkdownFromMcp();

          runBtn.addEventListener("click", async function(){
            const instruction = (instructionEl.value || "").trim();
            if(!instruction){
              alert("Skriv en instruktion till AI först.");
              return;
            }
            runBtn.disabled = true;
            runBtn.textContent = "AI körs…";
            if (workingIndicator) workingIndicator.style.display = "inline-flex";
            setNotes("<p class=\"muted\">AI arbetar, vänta...</p>");
            try {
              const fd = new FormData();
              fd.append("csrf_token", csrfToken);
              fd.append("file", file);
              fd.append("instruction", instruction);
              fd.append("system_prompt", systemPromptEl.value || "");
              fd.append("markdown", markdownEl.value || "");

              const res = await fetch(chatUrl, { method: "POST", body: fd });
              const data = await res.json();
              if(!res.ok || !data.ok){
                throw new Error(data.error || "Okänt fel");
              }
              markdownEl.value = data.updated_markdown || markdownEl.value;
              const noteText = (data.notes || "AI uppdaterade markdownen.").replace(/</g, "&lt;");
              setNotes("<p>" + noteText + "</p>");
            } catch(err){
              setNotes("<p style=\"color:var(--danger)\">Fel: " + String(err.message || err).replace(/</g, "&lt;") + "</p>");
            } finally {
              runBtn.disabled = false;
              runBtn.textContent = "✨ Kör AI";
              if (workingIndicator) workingIndicator.style.display = "none";
            }
          });
        })();
        </script>
      <?php endif; ?>
    </section>
  </div>
</main>
</body>
</html>
