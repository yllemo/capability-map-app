<?php
require __DIR__ . '/_auth.php';
require_edit();
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;
use App\Frontmatter;
use App\PathGuard;

$app = cfg('app');
$tax = cfg('taxonomy');

// Get available content directories and selected one
$contentDirs = get_content_dirs();
$editableDirs = editable_content_dirs();
$selectedKey = get_selected_content_key();
$contentDir = get_content_dir();
$repo = new CapabilityRepository($contentDir);

$rel = $_GET['file'] ?? '';
$abs = '';
$raw = '';
$meta = [];
$body = '';
$notice = '';
$noticeOk = true;

// Handle error and success messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($error === 'duplicate_id') {
  $notice = 'Fel: ID används redan av en annan förmåga'; $noticeOk = false;
} elseif ($error === 'missing_fields') {
  $notice = 'Fel: ID och namn måste fyllas i'; $noticeOk = false;
} elseif ($error === 'file_exists') {
  $notice = 'Fel: Det finns redan en fil med det namnet'; $noticeOk = false;
} elseif ($success === 'deleted') {
  $notice = 'Filen raderades';
} elseif ($success === 'saved') {
  $notice = 'Ändringarna sparades';
} elseif ($success === 'renamed') {
  $notice = 'Filen bytte namn';
}

if ($rel !== '') {
  try {
    $abs = PathGuard::safeJoin($contentDir, $rel);
    if (is_file($abs)) {
      $raw = (string)file_get_contents($abs);
      $parsed = Frontmatter::parse($raw);
      $meta = $parsed['meta'] ?? [];
      $body = $parsed['body'] ?? '';
      if (isset($meta['redirect_map'])) {
        header('Location: reference.php?map=' . rawurlencode($selectedKey) . '&file=' . rawurlencode($rel));
        exit;
      }
    }
  } catch (Throwable $e) {
    $notice = 'Ogiltig fil'; $noticeOk = false;
  }
}

// Collect every markdown file, along with its frontmatter, so the sidebar
// can show a readable label and the search box can filter by name/ID/area,
// not just the filename.
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

// Convert tags array to comma-separated string for editing
$tagsValue = '';
if (isset($meta['tags']) && is_array($meta['tags'])) {
  $tagsValue = implode(', ', $meta['tags']);
} elseif (isset($meta['tags']) && is_string($meta['tags'])) {
  $tagsValue = $meta['tags'];
}

$mapQuery = '?map=' . rawurlencode($selectedKey);
$backTarget = base_path('view/overview.php' . $mapQuery);
?><!doctype html>
<html lang="sv">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editor · Förmågekarta</title>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/editor.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
  <script defer src="<?= h(base_path('assets/editor.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#editor-main">Hoppa till editorn</a>
<header class="site-header">
  <a class="brand" href="<?= h($backTarget) ?>" style="text-decoration:none;color:inherit" title="Tillbaka till förmågekartan">
    <span class="brand-symbol" aria-hidden="true">▦</span>
    <div><strong>Förmågekarta</strong><span>Editor</span></div>
  </a>
  <nav aria-label="Verktyg">
    <?php if (count($editableDirs) > 1): ?>
      <div class="map-picker header-map-picker">
        <label for="contentDirSelect">Folder</label>
        <div>
          <select id="contentDirSelect" data-switch-url="<?= h(base_path('view/switch_content.php')) ?>" data-csrf="<?= h(csrf_token()) ?>">
            <?php foreach (map_picker_dirs($editableDirs) as $key => $dir): ?>
              <option value="<?= h($key) ?>" <?= $key === $selectedKey ? 'selected' : '' ?>><?= h($dir['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php else: ?>
      <span class="header-user"><?= h($contentDirs[$selectedKey]['label'] ?? 'content') ?></span>
    <?php endif; ?>
    <a href="<?= h($backTarget) ?>">← Karta</a>
    <a href="<?= h(base_path('view/index.php')) ?>">Viewer</a>
    <?php if ($rel !== ''): ?><a href="<?= h(base_path('ai/index.php?file=' . rawurlencode($rel))) ?>">AI Editor</a><?php endif; ?>
    <?php if (current_user() !== null): ?>
      <span class="header-user" title="Inloggad som <?= h(user_display_name()) ?>"><?= h(user_display_name()) ?></span>
      <a href="logout.php">Logga ut</a>
    <?php endif; ?>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main id="editor-main">
  <?php if ($notice): ?>
    <div class="editor-notice <?= $noticeOk ? 'is-ok' : 'is-error' ?>" role="status"><?= h($notice) ?></div>
  <?php endif; ?>

  <div class="editor-layout">
    <aside class="editor-sidebar" aria-label="Förmågor">
      <div class="editor-sidebar-head">
        <div class="editor-sidebar-title">
          <strong>Förmågor</strong>
          <span class="editor-file-count" id="editor-file-count"><?= count($files) ?> filer</span>
        </div>
        <label class="search-label" for="editor-search">Sök förmåga<input id="editor-search" type="search" placeholder="Namn, ID, område eller fil…" autocomplete="off"></label>
        <div class="editor-sidebar-actions">
          <button type="button" class="btn btn--ghost" onclick="showNewFolderModal()">+ Mapp</button>
          <a class="btn btn--ghost" href="new.php<?= h($mapQuery) ?>">+ Ny förmåga</a>
        </div>
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
        <a class="btn btn--ghost" href="folder.php?map=<?= rawurlencode($selectedKey) ?>">Hantera mappar</a>
        <?php if (App\Auth::isAdministrator()): ?><a class="btn btn--ghost" href="<?= h(base_path('admin/')) ?>">Admin</a><?php endif; ?>
        <a class="btn btn--ghost" href="import_json.php?map=<?= rawurlencode($selectedKey) ?>">Importera JSON</a>
        <?php if (get_content_root() === null): ?><a class="btn btn--ghost" href="migrate_content.php">Flytta innehåll till /content</a><?php endif; ?>
        <a class="btn btn--primary" href="download_zip.php?key=<?= h($selectedKey) ?>">⬇ Ladda ner ZIP</a>
      </div>
    </aside>

    <section class="editor-main" aria-label="Redigera förmåga">
      <?php if ($rel === ''): ?>
        <div class="editor-empty">
          <p class="eyebrow">EDITOR</p>
          <h1>Välj en förmåga att redigera</h1>
          <p>Välj en fil i listan till vänster, eller skapa en ny förmåga för att börja.</p>
          <a class="btn btn--primary" href="new.php<?= h($mapQuery) ?>">+ Skapa ny förmåga</a>
        </div>
      <?php else: ?>
        <form method="post" action="save.php" id="capForm">
          <input type="hidden" name="file" value="<?= h($rel) ?>">
          <?= csrf_field() ?>

          <div class="editor-main-head">
            <div>
              <p class="eyebrow">REDIGERAR<?php if (($meta['id'] ?? '') !== ''): ?> <span><?= h($meta['id']) ?></span><?php endif; ?></p>
              <h1><?= h(($meta['name'] ?? '') !== '' ? $meta['name'] : basename($rel, '.md')) ?></h1>
              <p class="editor-file-sub">Fil: <code><?= h($rel) ?></code></p>
            </div>
            <div class="editor-main-actions">
              <button class="btn btn--primary" type="submit">💾 Spara</button>
              <a class="btn btn--ghost" href="<?= h(base_path('view/capability_new.php?id=' . rawurlencode($meta['id'] ?? '') . '&map=' . rawurlencode($selectedKey))) ?>">Öppna i viewer</a>
              <button type="button" class="btn btn--ghost" onclick="showRawEditor()">Raw edit</button>
            </div>
          </div>

          <div class="editor-workspace">
          <fieldset class="editor-group editor-group--content">
            <legend>Innehåll</legend>
            <div class="editor-content-toolbar">
              <button class="btn btn--secondary" type="button" id="apply-capability-template" disabled>Använd förmågemall</button>
              <button class="btn btn--ghost" type="button" data-capability-link-picker data-targets-url="<?= h(base_path('editor/link_targets.php')) ?>" disabled>↗ Infoga förmågelänk</button>
            </div>
            <div class="editor-content-split">
              <div class="editor-content-col">
                <span class="editor-content-label">Markdown</span>
                <input type="hidden" name="body" id="bodyInput" value="<?= h($body) ?>">
                <textarea class="textarea" id="bodyFallback" style="display:none"><?= h($body) ?></textarea>
                <div id="markdownEditor" class="editor-codehost"></div>
              </div>
            </div>
          </fieldset>

          <aside class="editor-metadata" aria-label="Metadata">
            <h2>Metadata</h2>
            <?php $templateActive = ($meta['typ'] ?? '') === 'förmågebeskrivning'; ?>
            <input type="hidden" name="capability_template" id="capability-template" value="<?= $templateActive ? '1' : '0' ?>">
            <fieldset class="editor-group" id="template-fields" <?= $templateActive ? '' : 'hidden' ?>>
              <legend>Förmågemall · granskning</legend><div class="editor-fields">
              <?php foreach (['överordnad_förmåga'=>'Överordnad förmåga', 'version'=>'Version', 'senast_granskad'=>'Senast granskad', 'granskad_av'=>'Granskad av', 'notation'=>'Notation'] as $key=>$label): ?>
                <div class="editor-field editor-field--wide"><label for="template-<?= h($key) ?>"><?= h($label) ?></label><input class="input" id="template-<?= h($key) ?>" name="template_meta[<?= h($key) ?>]" value="<?= h((string)($meta[$key] ?? '')) ?>"></div>
              <?php endforeach; ?></div>
            </fieldset>
          <fieldset class="editor-group">
            <legend>Identitet</legend>
            <div class="editor-fields">
              <div class="editor-field"><label for="f-id">ID</label><input class="input" id="f-id" name="id" value="<?= h($meta['id'] ?? '') ?>"></div>
              <div class="editor-field"><label for="f-name">Namn</label><input class="input" id="f-name" name="name" value="<?= h($meta['name'] ?? '') ?>"></div>
              <div class="editor-field editor-field--wide"><label for="f-desc">Beskrivning</label><input class="input" id="f-desc" name="description" value="<?= h($meta['description'] ?? '') ?>"></div>
            </div>
          </fieldset>

          <fieldset class="editor-group">
            <legend>Klassificering</legend>
            <div class="editor-fields">
              <div class="editor-field">
                <label for="f-layer">Skikt</label>
                <select class="select" id="f-layer" name="layer">
                  <?php foreach (($tax['layers'] ?? []) as $k => $lbl): $sel = (($meta['layer'] ?? '') === $k) ? 'selected' : ''; ?>
                    <option value="<?= h($k) ?>" <?= $sel ?>><?= h($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="editor-field"><label for="f-area">Område</label><input class="input" id="f-area" name="area" value="<?= h($meta['area'] ?? '') ?>"></div>
              <div class="editor-field">
                <label for="f-level">Nivå</label>
                <select class="select" id="f-level" name="level">
                  <?php foreach (($tax['levels'] ?? [1, 2, 3]) as $lvl): $sel = ((int)($meta['level'] ?? 0) === (int)$lvl) ? 'selected' : ''; ?>
                    <option value="<?= h((string)$lvl) ?>" <?= $sel ?>><?= h((string)$lvl) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="editor-field">
                <label for="f-type">Typ</label>
                <select class="select" id="f-type" name="type">
                  <?php foreach (($tax['types'] ?? []) as $k => $lbl): $sel = (($meta['type'] ?? '') === $k) ? 'selected' : ''; ?>
                    <option value="<?= h($k) ?>" <?= $sel ?>><?= h($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </fieldset>

          <fieldset class="editor-group">
            <legend>Ägarskap, status &amp; bedömning</legend>
            <div class="editor-fields">
              <div class="editor-field"><label for="f-owner">Ansvarig</label><input class="input" id="f-owner" name="owner" value="<?= h($meta['owner'] ?? '') ?>"></div>
              <div class="editor-field"><label for="f-status">Status</label><input class="input" id="f-status" name="status" value="<?= h($meta['status'] ?? '') ?>"></div>
              <div class="editor-field"><label for="f-maturity">Mognad (1-5)</label><input class="input" id="f-maturity" name="maturity" value="<?= h((string)($meta['maturity'] ?? '')) ?>"></div>
              <div class="editor-field"><label for="f-criticality">Kritikalitet (1-5)</label><input class="input" id="f-criticality" name="criticality" value="<?= h((string)($meta['criticality'] ?? '')) ?>"></div>
              <div class="editor-field editor-field--wide"><label for="f-tags">Taggar <span class="muted">(kommaseparerade)</span></label><input class="input" id="f-tags" name="tags" value="<?= h($tagsValue) ?>" placeholder="ex: viktig, extern, digital"></div>
            </div>
          </fieldset>

          </aside>
          </div>

          <div class="editor-footer-actions">
            <a class="btn btn--ghost" href="download.php?file=<?= rawurlencode($rel) ?>" download="<?= h(basename($rel)) ?>" title="Ladda ner markdown-filen">⬇ Ladda ner</a>
            <button type="button" class="btn btn--ghost" onclick="showRenameModal('<?= h(addslashes(basename($rel, '.md'))) ?>')">Byt namn</button>
            <button type="button" class="btn btn--danger" onclick="confirmDelete('<?= h(addslashes($rel)) ?>')">Radera</button>
          </div>
        </form>

        <?php require __DIR__ . '/../app/templates/link_picker.php'; ?>

        <form id="deleteForm" method="post" action="delete.php" style="display:none">
          <input type="hidden" name="file" value="<?= h($rel) ?>">
          <?= csrf_field() ?>
        </form>

        <form id="renameForm" method="post" action="rename.php" style="display:none">
          <input type="hidden" name="old_file" value="<?= h($rel) ?>">
          <input type="hidden" name="new_name" id="renameNewName">
          <?= csrf_field() ?>
        </form>

        <div id="rawEditorModal" class="modal-overlay" style="display:none">
          <div class="modal-card modal-card--raw">
            <div class="modal-head">
              <strong>Raw editor</strong>
              <span class="muted">Redigera hela markdown-filen inklusive YAML frontmatter</span>
              <button type="button" class="btn btn--ghost" onclick="closeRawEditor()">✕</button>
            </div>
            <div class="modal-body">
              <form id="rawEditorForm" method="post" action="save_raw.php" class="raw-editor-form">
                <input type="hidden" name="file" value="<?= h($rel) ?>">
                <?= csrf_field() ?>
                <textarea id="rawContent" name="content" class="textarea raw-textarea"><?= h($raw) ?></textarea>
                <div class="modal-foot">
                  <div class="muted" style="font-size:12px">Tips: Var försiktig med YAML-syntaxen. Kontrollera indragningar och specialtecken.</div>
                  <div style="display:flex;gap:10px">
                    <button type="button" class="btn btn--ghost" onclick="closeRawEditor()">Avbryt</button>
                    <button type="submit" class="btn btn--primary">Spara raw</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <script>
        (function(){
          const bodyInput = document.getElementById("bodyInput");
          const fallbackTa = document.getElementById("bodyFallback");
          const editorHost = document.getElementById("markdownEditor");
          const form = document.getElementById("capForm");
          let hasUnsavedChanges = false;
          const originalContent = bodyInput.value;
          let monacoEditor = null;
          const templateBody = <?= json_encode(App\Frontmatter::parse((string)file_get_contents(__DIR__ . '/../app/templates/capabilities/business-capability.md'))['body'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
          document.getElementById('apply-capability-template').addEventListener('click', () => {
            if (getBodyValue().trim() && !confirm('Ersätta Markdown-innehållet med förmågemallen? Befintligt ID, namn och metadata behålls. Inget sparas förrän du klickar på Spara.')) return;
            const value = templateBody.replace('# <namn på verksamhetsförmågan>', '# ' + (form.elements.name.value.trim() || '<namn på verksamhetsförmågan>'));
            if (monacoEditor) {
              monacoEditor.pushUndoStop();
              monacoEditor.executeEdits('capability-template', [{range: monacoEditor.getModel().getFullModelRange(), text: value}]);
              monacoEditor.pushUndoStop();
            } else { fallbackTa.value = value; }
            document.getElementById('capability-template').value = '1';
            document.getElementById('template-fields').hidden = false;
            const defaults = {'version':'0.1', 'notation':'ArchiMate 4 (C260), modern färgpalett'};
            Object.entries(defaults).forEach(([key, value]) => {
              const field = form.elements.namedItem('template_meta[' + key + ']');
              if (!field.value) field.value = value;
            });
            if (!form.elements.status.value) form.elements.status.value = 'planerad';
            handleBodyInput();
            hasUnsavedChanges = true;
          });
          let linkSelection = null;
          document.addEventListener("capability-link-open", event => {
            if (monacoEditor) {
              linkSelection = monacoEditor.getSelection();
              event.detail.selectedText = monacoEditor.getModel().getValueInRange(linkSelection);
            } else {
              linkSelection = { start: fallbackTa.selectionStart, end: fallbackTa.selectionEnd };
              event.detail.selectedText = fallbackTa.value.slice(linkSelection.start, linkSelection.end);
            }
          });
          document.addEventListener("capability-link-insert", event => {
            if (monacoEditor) {
              monacoEditor.focus();
              monacoEditor.pushUndoStop();
              monacoEditor.executeEdits("capability-link", [{ range: linkSelection || monacoEditor.getSelection(), text: event.detail, forceMoveMarkers: true }]);
              monacoEditor.pushUndoStop();
            } else {
              fallbackTa.focus();
              fallbackTa.setRangeText(event.detail, linkSelection?.start ?? fallbackTa.selectionStart, linkSelection?.end ?? fallbackTa.selectionEnd, "end");
              handleBodyInput();
            }
          });

          function getBodyValue(){
            if (monacoEditor) return monacoEditor.getValue();
            if (fallbackTa) return fallbackTa.value;
            return bodyInput.value || "";
          }

          function setBodyValue(value){
            bodyInput.value = value;
          }

          function handleBodyInput(){
            const currentValue = getBodyValue();
            setBodyValue(currentValue);
            hasUnsavedChanges = (currentValue !== originalContent);
          }

          function initFallback(){
            if (!fallbackTa || !editorHost) return;
            editorHost.style.display = "none";
            fallbackTa.style.display = "block";
            fallbackTa.addEventListener("input", handleBodyInput);
          }

          function initMonaco(){
            return new Promise((resolve) => {
              if (!window.require) {
                const loader = document.createElement("script");
                loader.src = "https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js";
                loader.onload = setupRequire;
                loader.onerror = () => resolve(false);
                document.head.appendChild(loader);
              } else {
                setupRequire();
              }

              function setupRequire(){
                if (!window.require) {
                  resolve(false);
                  return;
                }
                window.require.config({ paths: { vs: "https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs" } });
                window.require(["vs/editor/editor.main"], function(){
                  if (!editorHost) {
                    resolve(false);
                    return;
                  }
                  monacoEditor = monaco.editor.create(editorHost, {
                    value: bodyInput.value || "",
                    language: "markdown",
                    theme: document.documentElement.dataset.theme === "dark" ? "vs-dark" : "vs",
                    automaticLayout: true,
                    minimap: { enabled: false },
                    wordWrap: "on",
                    fontSize: 14,
                    lineNumbers: "on",
                    scrollBeyondLastLine: false,
                  });
                  monacoEditor.onDidChangeModelContent(handleBodyInput);
                  resolve(true);
                }, function(){
                  resolve(false);
                });
              }
            });
          }

          initMonaco().then((ok) => {
            if (!ok) initFallback();
            document.querySelector("[data-capability-link-picker]").disabled = false;
            document.getElementById('apply-capability-template').disabled = false;
          });

          const inputs = form.querySelectorAll("input, select, textarea");
          inputs.forEach(input => {
            const originalValue = input.value;
            input.addEventListener("change", () => {
              if (input.value !== originalValue) {
                hasUnsavedChanges = true;
              }
            });
          });

          window.addEventListener("beforeunload", (e) => {
            if (hasUnsavedChanges) {
              e.preventDefault();
              e.returnValue = "";
              return "";
            }
          });

          form.addEventListener("submit", () => {
            setBodyValue(getBodyValue());
            hasUnsavedChanges = false;
          });
        })();
        </script>
      <?php endif; ?>
    </section>
  </div>
</main>

<div id="newFolderModal" class="modal-overlay" style="display:none">
  <div class="modal-card folder-modal-card">
    <div class="modal-head">
      <strong>Skapa ny folder</strong>
      <button type="button" class="btn btn--ghost" onclick="hideNewFolderModal()">✕</button>
    </div>
    <div class="modal-body">
      <form id="newFolderForm" class="folder-modal-body">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div>
          <label for="folderKey">Folder-nyckel (ex: content2)</label>
          <input class="input" name="key" id="folderKey" placeholder="content2" required pattern="[a-z0-9_\-]+" title="Endast små bokstäver, siffror, - och _" style="margin-top:6px">
          <div class="field-hint">Används i URL och filsystem</div>
        </div>
        <div>
          <label for="folderLabel">Visningsnamn</label>
          <input class="input" name="label" id="folderLabel" placeholder="Alternativ katalog" required style="margin-top:6px">
        </div>
        <div>
          <label for="folderDescription">Beskrivning (valfri)</label>
          <input class="input" name="description" id="folderDescription" placeholder="Beskrivning av denna folder" style="margin-top:6px">
        </div>
        <div id="folderError" class="field-error"></div>
        <div class="modal-form-foot">
          <button type="button" class="btn btn--ghost" onclick="hideNewFolderModal()">Avbryt</button>
          <button type="submit" class="btn btn--primary">Skapa</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
