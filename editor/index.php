<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;
use App\Frontmatter;
use App\Markdown;
use App\PathGuard;

$app = cfg('app');
$tax = cfg('taxonomy');

// Get available content directories and selected one
$contentDirs = get_content_dirs();
$selectedKey = get_selected_content_key();
$contentDir = get_content_dir();
$repo = new CapabilityRepository($contentDir);

$rel = $_GET['file'] ?? '';
$abs = '';
$raw = '';
$meta = [];
$body = '';
$notice = '';

// Handle error and success messages
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($error === 'duplicate_id') {
  $notice = 'Fel: ID används redan av en annan förmåga';
} elseif ($error === 'missing_fields') {
  $notice = 'Fel: ID och namn måste fyllas i';
} elseif ($error === 'file_exists') {
  $notice = 'Fel: Det finns redan en fil med det namnet';
} elseif ($success === 'deleted') {
  $notice = '✓ Filen raderades';
} elseif ($success === 'saved') {
  $notice = '✓ Ändringarna sparades';
} elseif ($success === 'renamed') {
  $notice = '✓ Filen bytte namn';
}

if ($rel !== '') {
  try {
    $abs = PathGuard::safeJoin($contentDir, $rel);
    if (is_file($abs)) {
      $raw = (string)file_get_contents($abs);
      $parsed = Frontmatter::parse($raw);
      $meta = $parsed['meta'] ?? [];
      $body = $parsed['body'] ?? '';
    }
  } catch (Throwable $e) {
    $notice = 'Ogiltig fil';
  }
}

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

$sidebar = '<div class="card"><div class="card__hd"><strong>Filer</strong><div style="display:flex;gap:8px"><button class="btn btn--ghost" onclick="showNewFolderModal()">+ Folder</button><a class="btn btn--ghost" href="new.php">+ Ny</a></div></div><div class="card__bd">';

// Folder selector dropdown
$sidebar .= '<div style="margin-bottom:10px">';
if (count($contentDirs) > 1) {
  $sidebar .= '<select id="contentDirSelect" class="select" style="width:100%;font-size:13px">';
  foreach ($contentDirs as $key => $dir) {
    $selected = ($key === $selectedKey) ? 'selected' : '';
    $sidebar .= '<option value="'.h($key).'" '.$selected.'>📁 '.h($dir['label']).'</option>';
  }
  $sidebar .= '</select>';
} else {
  $currentLabel = $contentDirs[$selectedKey]['label'] ?? 'content';
  $sidebar .= '<div class="muted" style="cursor:default">📁 '.h($currentLabel).'</div>';
}
$sidebar .= '</div>';
$sidebar .= '<div style="display:flex;flex-direction:column;gap:6px;max-height:65vh;overflow:auto">';
foreach ($files as $f) {
  $active = ($f === $rel) ? 'style="border-color: color-mix(in srgb, var(--primary) 60%, var(--border))"' : '';
  $sidebar .= '<a class="tile" '.$active.' href="index.php?file='.rawurlencode($f).'"><div class="tile__title" style="font-size:14px">'.h(basename($f)).'</div><div class="tile__desc" style="margin:0">'.h(dirname($f)).'</div></a>';
}
$sidebar .= '</div>';
$sidebar .= '<div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">';
$sidebar .= '<a class="btn btn--primary" href="download_zip.php?key='.h($selectedKey).'" style="text-align:center">';
$sidebar .= '<svg style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:4px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>';
$sidebar .= 'Ladda ner ZIP';
$sidebar .= '</a>';
$sidebar .= '<div style="display:flex;gap:8px"><a class="btn btn--ghost" href="logout.php">Logga ut</a><a class="btn btn--ghost" href="'.h(base_path('view/index.php')).'">Viewer</a></div>';
$sidebar .= '</div>';
$sidebar .= '</div></div>';

$editor = '<div class="card"><div class="card__hd"><strong>Editor</strong><span class="muted">Markdown + YAML frontmatter</span></div><div class="card__bd">';
if ($notice) $editor .= '<div class="badge" style="margin-bottom:10px">'.h($notice).'</div>';

if ($rel === '') {
  $editor .= '<p class="muted">Välj en fil till vänster, eller skapa en ny förmåga.</p>';
} else {
  $editor .= '<form method="post" action="save.php" class="grid" style="gap:10px">';
  $editor .= '<input type="hidden" name="file" value="'.h($rel).'">';
  $editor .= csrf_field();
  $editor .= '<div class="grid grid--2" style="gap:10px">';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">ID</label><input class="input" name="id" value="'.h($meta['id'] ?? '').'"></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Namn</label><input class="input" name="name" value="'.h($meta['name'] ?? '').'"></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Skikt</label><select class="select" name="layer">';
  foreach (($tax['layers'] ?? []) as $k=>$lbl){ $sel = (($meta['layer'] ?? '')===$k)?'selected':''; $editor .= '<option value="'.h($k).'" '.$sel.'>'.h($lbl).'</option>'; }
  $editor .= '</select></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Område</label><input class="input" name="area" value="'.h($meta['area'] ?? '').'"></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Level</label><select class="select" name="level">';
  foreach (($tax['levels'] ?? [1,2,3]) as $lvl){ $sel = ((int)($meta['level'] ?? 0)===(int)$lvl)?'selected':''; $editor .= '<option value="'.h((string)$lvl).'" '.$sel.'>'.h((string)$lvl).'</option>'; }
  $editor .= '</select></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Typ</label><select class="select" name="type">';
  foreach (($tax['types'] ?? []) as $k=>$lbl){ $sel = (($meta['type'] ?? '')===$k)?'selected':''; $editor .= '<option value="'.h($k).'" '.$sel.'>'.h($lbl).'</option>'; }
  $editor .= '</select></div>';
  $editor .= '<div class="grid grid--2" style="gap:10px;grid-column:1/-1">';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Owner</label><input class="input" name="owner" value="'.h($meta['owner'] ?? '').'"></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Status</label><input class="input" name="status" value="'.h($meta['status'] ?? '').'"></div>';
  $editor .= '</div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Beskrivning</label><input class="input" name="description" value="'.h($meta['description'] ?? '').'"></div>';
  $editor .= '<div class="grid grid--2" style="gap:10px">';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Maturity (1-5)</label><input class="input" name="maturity" value="'.h((string)($meta['maturity'] ?? '')).'"></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Criticality (1-5)</label><input class="input" name="criticality" value="'.h((string)($meta['criticality'] ?? '')).'"></div>';
  $editor .= '</div>';
  $editor .= '</div>'; // end meta grid

  $editor .= '<div class="grid grid--2" style="gap:10px">';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Markdown</label><textarea class="textarea" name="body">'.h($body).'</textarea></div>';
  $editor .= '<div><label class="muted" style="display:block;margin-bottom:6px">Preview</label><div class="card" style="height:52vh; overflow:auto"><div class="card__bd prose" id="preview"></div></div></div>';
  $editor .= '</div>';

  $editor .= '<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">';
  $editor .= '<button class="btn btn--primary" type="submit">Spara</button>';
  $editor .= '<a class="btn btn--ghost" href="'.h(base_path('view/capability.php?id=' . rawurlencode($meta['id'] ?? ''))).'">Öppna i viewer</a>';
  $editor .= '<a class="btn btn--ghost" href="download.php?file='.rawurlencode($rel).'" download="'.h(basename($rel)).'" title="Ladda ner markdown-filen">';
  $editor .= '<svg style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:4px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>';
  $editor .= 'Ladda ner';
  $editor .= '</a>';
  $editor .= '<button type="button" class="btn btn--ghost" onclick="showRenameModal(\''.h(addslashes(basename($rel, '.md'))).'\')">Byt namn</button>';
  $editor .= '<button type="button" class="btn btn--danger" onclick="confirmDelete(\''.h(addslashes($rel)).'\')">Radera</button>';
  $editor .= '<span class="muted">Fil: <code>'.h($rel).'</code></span>';
  $editor .= '</div>';
  $editor .= '</form>';

  // Delete form (hidden)
  $editor .= '<form id="deleteForm" method="post" action="delete.php" style="display:none">';
  $editor .= '<input type="hidden" name="file" value="'.h($rel).'">';
  $editor .= csrf_field();
  $editor .= '</form>';

  // Rename form (hidden)
  $editor .= '<form id="renameForm" method="post" action="rename.php" style="display:none">';
  $editor .= '<input type="hidden" name="old_file" value="'.h($rel).'">';
  $editor .= '<input type="hidden" name="new_name" id="renameNewName">';
  $editor .= csrf_field();
  $editor .= '</form>';

  $editor .= '<script>
    (function(){
      const ta = document.querySelector("textarea[name=body]");
      const pv = document.getElementById("preview");
      const form = document.querySelector("form[action=\'save.php\']");
      let hasUnsavedChanges = false;
      const originalContent = ta.value;

      async function render(){
        const fd = new FormData();
        fd.set("md", ta.value);
        const res = await fetch("render.php", {method:"POST", body: fd});
        pv.innerHTML = await res.text();
      }

      // Track changes in textarea and all inputs
      ta.addEventListener("input", ()=>{
        hasUnsavedChanges = (ta.value !== originalContent);
        window.clearTimeout(window.__pvT);
        window.__pvT=setTimeout(render, 150);
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

      // Warn before leaving page with unsaved changes
      window.addEventListener("beforeunload", (e) => {
        if (hasUnsavedChanges) {
          e.preventDefault();
          e.returnValue = "";
          return "";
        }
      });

      // Reset flag on form submit
      form.addEventListener("submit", () => {
        hasUnsavedChanges = false;
      });

      render();
    })();
  </script>';
}
$editor .= '</div></div>';

$content = '<div class="grid grid--editor">'.$sidebar.$editor.'</div>';
$title = 'Editor';
$activeNav = 'editor';
$containerClass = 'container--wide';

ob_start();
require __DIR__ . '/../app/templates/layout.php';
echo ob_get_clean();
?>

<!-- New Folder Modal -->
<div id="newFolderModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
  <div class="card" style="max-width:500px;margin:20px;">
    <div class="card__hd">
      <strong>Skapa ny folder</strong>
      <button class="btn btn--ghost" onclick="hideNewFolderModal()">✕</button>
    </div>
    <div class="card__bd">
      <form id="newFolderForm" style="display:flex;flex-direction:column;gap:12px">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <div>
          <label class="muted" style="display:block;margin-bottom:6px">Folder-nyckel (ex: content2)</label>
          <input class="input" name="key" id="folderKey" placeholder="content2" required pattern="[a-z0-9_\-]+" title="Endast små bokstäver, siffror, - och _">
          <div class="muted" style="font-size:11px;margin-top:4px">Används i URL och filsystem</div>
        </div>
        <div>
          <label class="muted" style="display:block;margin-bottom:6px">Visningsnamn</label>
          <input class="input" name="label" id="folderLabel" placeholder="Alternativ katalog" required>
        </div>
        <div>
          <label class="muted" style="display:block;margin-bottom:6px">Beskrivning (valfri)</label>
          <input class="input" name="description" id="folderDescription" placeholder="Beskrivning av denna folder">
        </div>
        <div id="folderError" class="badge" style="display:none;border-color:var(--danger);color:var(--danger)"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" class="btn btn--ghost" onclick="hideNewFolderModal()">Avbryt</button>
          <button type="submit" class="btn btn--primary">Skapa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Folder switcher
const csrfToken = '<?= h(csrf_token()) ?>';
const contentDirSelect = document.getElementById('contentDirSelect');
if(contentDirSelect){
  contentDirSelect.addEventListener('change', async (e) => {
    const key = e.target.value;
    const originalValue = e.target.value;

    try {
      const formData = new FormData();
      formData.append('key', key);
      formData.append('csrf_token', csrfToken);

      const response = await fetch('<?= h(base_path('view/switch_content.php')) ?>', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if(result.success){
        window.location.reload();
      } else {
        alert('Kunde inte byta katalog: ' + (result.error || 'Okänt fel'));
        e.target.value = originalValue;
      }
    } catch(error) {
      console.error('Error switching content directory:', error);
      alert('Ett fel uppstod vid byte av katalog');
      e.target.value = originalValue;
    }
  });
}

// New folder modal
function showNewFolderModal(){
  const modal = document.getElementById('newFolderModal');
  if(modal){
    modal.style.display = 'flex';
    document.getElementById('folderKey').focus();
  }
}

function hideNewFolderModal(){
  const modal = document.getElementById('newFolderModal');
  if(modal) modal.style.display = 'none';
  document.getElementById('newFolderForm').reset();
  document.getElementById('folderError').style.display = 'none';
}

// Handle new folder form submission
const newFolderForm = document.getElementById('newFolderForm');
if(newFolderForm){
  newFolderForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const errorDiv = document.getElementById('folderError');
    const submitBtn = e.target.querySelector('button[type=submit]');

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Skapar...';
    errorDiv.style.display = 'none';

    try {
      const response = await fetch('create_folder.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if(result.success){
        // Reload page to show new folder
        window.location.reload();
      } else {
        errorDiv.textContent = result.error || 'Ett fel uppstod';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Skapa';
      }
    } catch(error) {
      console.error('Error creating folder:', error);
      errorDiv.textContent = 'Ett fel uppstod vid skapande av folder';
      errorDiv.style.display = 'block';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Skapa';
    }
  });
}

// Close modal on outside click
document.getElementById('newFolderModal')?.addEventListener('click', (e) => {
  if(e.target.id === 'newFolderModal') hideNewFolderModal();
});

// Delete confirmation
function confirmDelete(filename) {
  if (confirm('Är du säker på att du vill radera "' + filename + '"?\n\nDenna åtgärd kan inte ångras.')) {
    document.getElementById('deleteForm').submit();
  }
}

// Rename modal
function showRenameModal(currentName) {
  const newName = prompt('Ange nytt filnamn (utan .md):', currentName);
  if (newName && newName.trim() !== '' && newName !== currentName) {
    document.getElementById('renameNewName').value = newName.trim();
    document.getElementById('renameForm').submit();
  }
}
</script>
