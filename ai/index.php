<?php
require __DIR__ . '/../editor/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');

$contentDir = get_content_dir();
$aiCfg = cfg('ai');
$systemPrompt = (string)($aiCfg['default_system_prompt'] ?? '');
$rel = trim((string)($_GET['file'] ?? ''));
$notice = isset($_GET['saved']) ? '✓ Filen sparades' : '';
$markdown = '';

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($contentDir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
  if (!$f->isFile()) continue;
  $ext = strtolower($f->getExtension());
  if ($ext !== 'md' && $ext !== 'markdown') continue;
  $relPath = ltrim(str_replace($contentDir, '', $f->getPathname()), DIRECTORY_SEPARATOR);
  $files[] = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
}
sort($files);
if ($rel === '' && !empty($files)) $rel = $files[0];

$sidebar = '<div class="card"><div class="card__hd"><strong>AI filer</strong></div><div class="card__bd" style="display:flex;flex-direction:column;gap:6px;max-height:75vh;overflow:auto">';
foreach ($files as $f) {
  $active = ($f === $rel) ? 'style="border-color: color-mix(in srgb, var(--primary) 60%, var(--border))"' : '';
  $sidebar .= '<a class="tile" ' . $active . ' href="index.php?file=' . rawurlencode($f) . '"><div class="tile__title">' . h(basename($f)) . '</div><div class="tile__desc">' . h(dirname($f)) . '</div></a>';
}
$sidebar .= '</div></div>';

$mcpUrl = absolute_url('mcp/index.php');
$mcpPath = base_path('mcp/index.php');
$chatUrl = base_path('ai/chat.php');
$saveUrl = base_path('ai/save.php');

$main = '<div class="card"><div class="card__hd"><strong>AI Markdown Editor</strong><span class="muted">OpenAI default + MCP skills via /mcp/index.php</span></div><div class="card__bd">';
if ($notice) $main .= '<div class="badge" style="margin-bottom:10px">' . h($notice) . '</div>';
if ($rel === '') {
  $main .= '<p class="muted">Ingen markdown-fil hittades.</p>';
} else {
  $main .= '<form id="aiSaveForm" method="post" action="' . h($saveUrl) . '" class="grid" style="gap:10px">';
  $main .= csrf_field();
  $main .= '<input type="hidden" name="file" value="' . h($rel) . '">';
  $main .= '<div><label class="muted" style="display:block;margin-bottom:6px">Fil</label><input class="input" readonly value="' . h($rel) . '"></div>';
  $main .= '<details style="margin-bottom:8px"><summary class="muted" style="cursor:pointer;font-weight:600">Visa systemprompt</summary><div style="margin-top:8px"><textarea id="systemPrompt" class="textarea" style="height:90px">' . h($systemPrompt) . '</textarea></div></details>';
  $main .= '<div><label class="muted" style="display:block;margin-bottom:6px">Instruktion till AI</label><textarea id="instruction" class="textarea" style="height:64px;min-height:56px;max-height:120px;resize:vertical" placeholder="Ex: Förtydliga beskrivning och förbättra tags"></textarea></div>';
  $main .= '<div class="grid grid--2" style="gap:10px">';
  $main .= '<div><label class="muted" style="display:block;margin-bottom:6px">Markdown (redigerbar)</label><textarea id="markdownEditor" class="textarea" style="height:38vh;min-height:260px;max-height:520px;font-family:ui-monospace,monospace">' . h($markdown) . '</textarea><input type="hidden" id="markdownInput" name="markdown"></div>';
  $main .= '<div><label class="muted" style="display:block;margin-bottom:6px">AI-respons</label><div id="aiNotes" class="card" style="height:38vh;min-height:260px;max-height:520px;overflow:auto"><div class="card__bd prose"><p class="muted">Ingen AI-korning ännu.</p></div></div></div>';
  $main .= '</div>';
  $main .= '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">';
  $main .= '<button type="button" class="btn btn--secondary" id="runAiBtn">Kör AI</button>';
  $main .= '<button type="submit" class="btn btn--primary">Spara fil</button>';
  $main .= '<a class="btn btn--ghost" href="' . h(base_path('view/capability.php?id=' . rawurlencode(pathinfo($rel, PATHINFO_FILENAME)))) . '">Öppna i viewer</a>';
  $main .= '<span id="aiWorkingIndicator" class="badge" style="display:none;border-color:var(--primary);color:var(--primary)" aria-live="polite">AI arbetar...</span>';
  $main .= '<span class="muted">MCP: <code>' . h($mcpUrl) . '</code></span>';
  $main .= '</div>';
  $main .= '</form>';
  $main .= '<script>
    (function(){
      const runBtn = document.getElementById("runAiBtn");
      const notes = document.getElementById("aiNotes");
      const instructionEl = document.getElementById("instruction");
      const systemPromptEl = document.getElementById("systemPrompt");
      const markdownEl = document.getElementById("markdownEditor");
      const markdownInput = document.getElementById("markdownInput");
      const saveForm = document.getElementById("aiSaveForm");
      const workingIndicator = document.getElementById("aiWorkingIndicator");
      const mcpPath = ' . json_encode($mcpPath) . ';
      const file = ' . json_encode($rel) . ';

      function setNotes(html){
        notes.innerHTML = "<div class=\"card__bd prose\">" + html + "</div>";
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
          setNotes("<p style=\"color:#b91c1c\">Kunde inte läsa markdown via MCP: " + String(err.message || err).replace(/</g, "&lt;") + "</p>");
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
        runBtn.textContent = "AI körs...";
        if (workingIndicator) workingIndicator.style.display = "inline-flex";
        setNotes("<p class=\"muted\">AI arbetar, vänta...</p>");
        try {
          const fd = new FormData();
          fd.append("csrf_token", ' . json_encode(csrf_token()) . ');
          fd.append("file", ' . json_encode($rel) . ');
          fd.append("instruction", instruction);
          fd.append("system_prompt", systemPromptEl.value || "");
          fd.append("markdown", markdownEl.value || "");

          const res = await fetch(' . json_encode($chatUrl) . ', { method: "POST", body: fd });
          const data = await res.json();
          if(!res.ok || !data.ok){
            throw new Error(data.error || "Okänt fel");
          }
          markdownEl.value = data.updated_markdown || markdownEl.value;
          const noteText = (data.notes || "AI uppdaterade markdownen.").replace(/</g, "&lt;");
          setNotes("<p>" + noteText + "</p>");
        } catch(err){
          setNotes("<p style=\"color:#b91c1c\">Fel: " + String(err.message || err).replace(/</g, "&lt;") + "</p>");
        } finally {
          runBtn.disabled = false;
          runBtn.textContent = "Kör AI";
          if (workingIndicator) workingIndicator.style.display = "none";
        }
      });
    })();
  </script>';
}
$main .= '</div></div>';

$content = '<div class="grid grid--editor">' . $sidebar . $main . '</div>';
$title = 'AI Editor';
$activeNav = 'ai';
$containerClass = 'container--wide';

ob_start();
require __DIR__ . '/../app/templates/layout.php';
echo ob_get_clean();
