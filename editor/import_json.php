<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityJsonImport;

$dirs = get_content_dirs();
$editableDirs = editable_content_dirs();
$selectedKey = get_selected_content_key();
$error = '';
$success = '';
$idPrefix = is_string($_POST['id_prefix'] ?? '') ? trim($_POST['id_prefix'] ?? '') : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!csrf_verify((string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('Sessionen har gått ut. Ladda om sidan och försök igen.');
    $key = (string)($_POST['map'] ?? '');
    if (!isset($dirs[$key])) throw new RuntimeException('Välj en giltig målkarta.');
    require_edit($key);
    $selectedKey = $key;
    $file = $_FILES['json_file'] ?? [];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Filen kunde inte laddas upp. Välj en JSON-fil inom serverns uppladdningsgräns.');
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Filen får vara högst 5 MB.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Ogiltig uppladdning.');
    $json = file_get_contents($file['tmp_name']);
    if ($json === false) throw new RuntimeException('Filen kunde inte läsas.');
    if (!is_string($_POST['id_prefix'] ?? '')) throw new RuntimeException('ID-prefix måste vara text.');
    $import = CapabilityJsonImport::convert($json, $idPrefix);
    foreach ($import['files'] as $markdown) {
      $meta = App\Frontmatter::parse($markdown)['meta'];
      if (!isset(cfg('taxonomy')['layers'][$meta['layer']])) throw new RuntimeException('Kartans taxonomi saknar skiktet ' . $meta['layer'] . '.');
    }
    CapabilityJsonImport::save($import, (string)$dirs[$key]['path']);
    set_content_dir($key);
    $success = count($import['files']) . ' förmågor importerades till ' . ($dirs[$key]['label'] ?? $key) . '.';
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

$title = 'Importera JSON';
$activeNav = 'editor';
$content = '<div class="card" style="max-width:760px;margin:0 auto"><div class="card__hd"><strong>Importera JSON</strong><a class="btn btn--ghost" href="index.php?map='.rawurlencode($selectedKey).'">← Till editor</a></div><div class="card__bd">';
if ($error) $content .= '<p role="alert">'.h($error).'</p>';
if ($success) $content .= '<p role="status">'.h($success).'</p><p><a class="btn btn--primary" href="'.h(base_path('view/index.php?map='.rawurlencode($selectedKey))).'">Visa förmågekartan</a></p>';
$content .= '<p>Läs in en JSON-fil skapad enligt capability-map-skill / export-html-json.md. Varje förmåga blir en Markdown-fil i den valda kartan.</p><p class="muted">Skikten strategic, core och support översätts till appens tre skikt. Status blir mognad 1–5. Område sätts till ”Importerade förmågor”. Originalfilens information, inklusive organisation och kartbeskrivning, sparas i source.json. Befintliga filer skrivs inte över.</p>';
$content .= '<form method="post" enctype="multipart/form-data" class="grid" style="gap:12px">'.csrf_field();
$content .= '<label for="map">Målkarta</label><select class="select" id="map" name="map">';
foreach (map_picker_dirs($editableDirs) as $key => $dir) $content .= '<option value="'.h($key).'"'.($key === $selectedKey ? ' selected' : '').'>'.h($dir['label'] ?? $key).'</option>';
$content .= '</select><label for="id-prefix">ID-prefix (valfritt)</label><input class="input" id="id-prefix" name="id_prefix" value="'.h($idPrefix).'" placeholder="cap-intra-" maxlength="80" pattern="[a-z][a-z0-9-]*"><p class="muted">Exempel: cap-intra- ger cap-intra-1, cap-intra-2 osv. Numreringen börjar på 1 i filens ordning. Tomt fält behåller automatiska ID:n. Befintliga ID:n skrivs inte över.</p><label for="json_file">JSON-fil (högst 5 MB)</label><input class="input" type="file" id="json_file" name="json_file" accept=".json,application/json" required><button class="btn btn--primary" type="submit">Importera förmågor</button></form></div></div>';
require __DIR__ . '/../app/templates/layout.php';
