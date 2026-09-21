<?php
require __DIR__ . '/_auth.php';
require_edit();
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;
use App\Logger;
use App\PathGuard;

$tax = cfg('taxonomy');
$selectedKey = get_selected_content_key();
$contentDir = get_content_dir();
$values = ['id' => '', 'name' => '', 'layer' => array_key_first($tax['layers'] ?? []) ?? '', 'area' => '', 'type' => array_key_first($tax['types'] ?? []) ?? '', 'level' => '2', 'description' => '', 'maturity' => '1', 'body' => '', 'url' => '', 'source_id' => '', 'source_status' => ''];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !csrf_verify($token)) throw new RuntimeException('Ladda om sidan och försök igen.');
    if (($_POST['creation_mode'] ?? '') === 'reference') {
      $selection = $_POST['reference_target'] ?? '';
      if (!is_string($selection)) throw new RuntimeException('Välj en originalförmåga.');
      $referenceId = 'cap-ref-' . bin2hex(random_bytes(8));
      $input = $_POST['reference_options'] ?? [];
      if (!is_array($input)) throw new RuntimeException('Ogiltiga kortinställningar.');
      $overrides = App\CapabilityReference::overrides($input, $tax);
      $text = App\CapabilityReference::markdown($referenceId, $selection, readable_content_dirs(), $overrides);
      $file = 'references/' . $referenceId . '.md';
      $abs = PathGuard::safeJoin($contentDir, $file);
      if (!is_dir(dirname($abs)) && !@mkdir(dirname($abs), 0775, true)) throw new RuntimeException('Kunde inte skapa referenskatalogen.');
      $handle = @fopen($abs, 'x');
      if (!$handle) throw new RuntimeException('Kunde inte skapa referensfilen.');
      $written = fwrite($handle, $text);
      fclose($handle);
      if ($written !== strlen($text)) { @unlink($abs); throw new RuntimeException('Kunde inte skriva referensfilen.'); }
      Logger::audit('capability_reference_created', ['file' => $file, 'id' => $referenceId]);
      header('Location: index.php?map=' . rawurlencode($selectedKey) . '&file=' . rawurlencode($file));
      exit;
    }
    foreach ($values as $key => $_) {
      if (!is_string($_POST[$key] ?? '')) throw new RuntimeException('Fälten måste innehålla text.');
      $values[$key] = $key === 'body' ? ($_POST[$key] ?? '') : trim($_POST[$key] ?? '');
    }
    if ($values['id'] === '' || $values['name'] === '') throw new RuntimeException('Fyll i ID och namn.');
    if (!isset($tax['layers'][$values['layer']]) || !isset($tax['types'][$values['type']])) throw new RuntimeException('Välj ett giltigt skikt och en giltig typ.');
    if (!in_array((int)$values['level'], $tax['levels'] ?? [1, 2, 3], true) || !in_array($values['maturity'], ['1', '2', '3', '4', '5'], true)) throw new RuntimeException('Välj giltig nivå och mognad.');
    if ($values['url'] !== '' && (!filter_var($values['url'], FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($values['url'], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true))) throw new RuntimeException('Länken måste vara en HTTP- eller HTTPS-adress.');
    $repo = new CapabilityRepository($contentDir);
    if ($repo->byId($values['id'])) throw new RuntimeException('ID används redan av en annan förmåga.');
    $slug = mb_strtolower($values['name'], 'UTF-8');
    $slug = preg_replace('/[^a-z0-9\s\-åäö]/u', '', $slug);
    $slug = preg_replace('/\s+/', '-', trim($slug)) ?: 'new-capability';
    $file = $values['layer'] . '/' . $slug . '.md';
    $abs = PathGuard::safeJoin($contentDir, $file);
    $meta = $values;
    unset($meta['body']);
    $meta['level'] = (int)$meta['level'];
    $meta['maturity'] = (int)$meta['maturity'];
    $meta['owner'] = '';
    $meta['status'] = 'planerad';
    $meta['criticality'] = 1;
    $meta['updated'] = date('Y-m-d');
    foreach (['url', 'source_id', 'source_status'] as $field) if ($meta[$field] === '') unset($meta[$field]);
    $text = "---\n";
    foreach ($meta as $key => $value) {
      if (is_string($value)) $value = preg_replace('/\R/u', ' ', $value);
      $text .= $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }
    $body = $values['body'] !== '' ? $values['body'] : '# ' . preg_replace('/\R/u', ' ', $values['name']) . "\n\nSkriv förmågebeskrivningen här.\n";
    $text .= "---\n\n" . $body;
    if (!is_dir(dirname($abs)) && !@mkdir(dirname($abs), 0775, true)) throw new RuntimeException('Kunde inte skapa förmågans katalog.');
    $handle = @fopen($abs, 'x');
    if (!$handle) throw new RuntimeException('Filen finns redan eller kan inte skapas.');
    $written = fwrite($handle, $text);
    fclose($handle);
    if ($written !== strlen($text)) { @unlink($abs); throw new RuntimeException('Kunde inte skriva hela filen.'); }
    Logger::audit('capability_created', ['file' => $file, 'id' => $values['id']]);
    header('Location: index.php?map=' . rawurlencode($selectedKey) . '&file=' . rawurlencode($file));
    exit;
  } catch (Throwable $e) { $error = $e->getMessage(); }
}

$maturityOptions = [1 => '1 · Initial', 2 => '2 · Under utveckling', 3 => '3 · Definierad', 4 => '4 · Hanterad', 5 => '5 · Optimerad'];
$mapQuery = '?map=' . rawurlencode($selectedKey);
$backTarget = base_path('view/overview.php' . $mapQuery);
$editorTarget = base_path('editor/index.php' . $mapQuery);
?><!doctype html>
<html lang="sv">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ny förmåga · Förmågekarta</title>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/editor.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
  <script defer src="<?= h(base_path('assets/editor.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#new-main">Hoppa till formuläret</a>
<header class="site-header">
  <a class="brand" href="<?= h($backTarget) ?>" style="text-decoration:none;color:inherit" title="Tillbaka till förmågekartan">
    <span class="brand-symbol" aria-hidden="true">▦</span>
    <div><strong>Förmågekarta</strong><span>Editor</span></div>
  </a>
  <nav aria-label="Verktyg">
    <a href="<?= h($backTarget) ?>">← Karta</a>
    <a href="<?= h($editorTarget) ?>">Editor</a>
    <?php if (current_user() !== null): ?>
      <span class="header-user" title="Inloggad som <?= h(user_display_name()) ?>"><?= h(user_display_name()) ?></span>
      <a href="logout.php">Logga ut</a>
    <?php endif; ?>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main id="new-main">
  <div class="new-cap-shell">
    <section class="editor-main" aria-label="Skapa ny förmåga">
      <div class="editor-main-head">
        <div>
          <p class="eyebrow">EDITOR</p>
          <h1>Skapa ny förmåga</h1>
          <p class="editor-file-sub">Fyll i uppgifterna nedan, länka en befintlig förmåga, eller importera från JSON.</p>
        </div>
        <div class="editor-main-actions">
          <a class="btn btn--ghost" href="<?= h($editorTarget) ?>">← Till editor</a>
        </div>
      </div>

      <?php if ($error): ?><div class="editor-notice is-error" role="alert"><?= h($error) ?></div><?php endif; ?>

      <details class="ai-details" <?= ($_POST['creation_mode'] ?? '') === 'reference' ? 'open' : '' ?>>
        <summary>Länka en befintlig förmåga (referenskort)</summary>
        <p class="muted">Välj originalet från någon av kartorna. Kortet följer originalets innehåll. Du kan välja egna skikt och nivåer nedan. Klick på kortet öppnar originalet. Endast omstyrningen och dina egna inställningar sparas i den aktuella kartan.</p>
        <form method="post" action="new.php?map=<?= h(rawurlencode($selectedKey)) ?>" class="grid" style="gap:10px">
          <?= csrf_field() ?><input type="hidden" name="creation_mode" value="reference">
          <div class="editor-field" id="reference-search-field">
            <label for="reference-search">Sök originalförmåga</label>
            <input class="input" type="search" id="reference-search" placeholder="Sök på namn, karta eller ID…" aria-controls="reference-target" autocomplete="off">
            <p class="field-hint">Skriv för att filtrera listan nedan på namn, karta eller ID. Klicka sedan på originalförmågan du vill länka.</p>
            <p class="field-hint" id="reference-search-status" role="status" aria-live="polite"></p>
            <noscript><p>Sökningen kräver JavaScript. Du kan fortfarande välja en förmåga i listan nedan.</p></noscript>
          </div>
          <div class="editor-field">
            <label for="reference-target">Originalförmåga (karta · namn · ID)</label>
            <select class="select" id="reference-target" name="reference_target" size="7" style="height:auto;min-height:180px" required>
              <option value="">Välj förmåga…</option>
              <?php foreach (App\CapabilityReference::choices(readable_content_dirs()) as $choice): ?>
              <option value="<?= h(json_encode([$choice['map'], $choice['id']], JSON_UNESCAPED_UNICODE)) ?>"><?= h($choice['label'] . ' · ' . $choice['name'] . ' · ' . $choice['id']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php $referenceValues = []; require __DIR__ . '/../app/templates/reference_options.php'; ?>
          <div class="editor-footer-actions" style="border-top:0;padding-top:4px">
            <button class="btn btn--primary" type="submit">Skapa referenskort</button>
          </div>
        </form>
      </details>

      <details class="ai-details">
        <summary>Importera en förmåga från JSON</summary>
        <p class="muted">Samma format som Importera JSON. Välj en fil (högst 5 MB) och sedan en förmåga. Formuläret fylls i; inget sparas förrän du klickar på Skapa.</p>
        <form id="json-preview-form" enctype="multipart/form-data" action="preview_json.php" method="post" class="grid" style="gap:10px">
          <?= csrf_field() ?>
          <div class="editor-field">
            <label for="json-id-prefix">ID-prefix (valfritt)</label>
            <input class="input" id="json-id-prefix" name="id_prefix" placeholder="cap-intra-" maxlength="80" pattern="[a-z][a-z0-9-]*">
            <p class="field-hint">cap-intra- ger cap-intra-1, cap-intra-2 osv. efter ordningen i JSON-filen. Du kan ändra ID i formuläret innan du sparar.</p>
          </div>
          <div class="editor-field">
            <label for="json-file">JSON-fil</label>
            <input class="input" type="file" id="json-file" name="json_file" accept=".json,application/json" required>
          </div>
          <div class="editor-footer-actions" style="border-top:0;padding-top:4px">
            <button class="btn btn--secondary" type="submit">Läs JSON</button>
          </div>
        </form>
        <p id="json-import-message" class="muted" role="status" aria-live="polite" style="margin:0"></p>
        <div id="json-choice" hidden style="margin-top:10px">
          <div class="editor-field">
            <label for="json-capability">Välj förmåga</label>
            <select class="select" id="json-capability"></select>
          </div>
          <p id="json-choice-description" class="muted"></p>
          <button class="btn btn--secondary" type="button" id="use-json-capability">Fyll i formuläret</button>
        </div>
      </details>

      <form id="new-capability-form" method="post" action="new.php?map=<?= h(rawurlencode($selectedKey)) ?>">
        <?= csrf_field() ?>

        <fieldset class="editor-group">
          <legend>Identitet</legend>
          <div class="editor-fields">
            <div class="editor-field"><label for="new-id">ID</label><input class="input" id="new-id" name="id" value="<?= h($values['id']) ?>" required></div>
            <div class="editor-field"><label for="new-name">Namn</label><input class="input" id="new-name" name="name" value="<?= h($values['name']) ?>" required></div>
            <div class="editor-field"><label for="new-area">Område</label><input class="input" id="new-area" name="area" value="<?= h($values['area']) ?>"></div>
            <div class="editor-field editor-field--wide"><label for="new-description">Beskrivning</label><input class="input" id="new-description" name="description" value="<?= h($values['description']) ?>"></div>
          </div>
        </fieldset>

        <fieldset class="editor-group">
          <legend>Klassificering &amp; bedömning</legend>
          <div class="editor-fields">
            <div class="editor-field">
              <label for="new-layer">Skikt</label>
              <select class="select" id="new-layer" name="layer">
                <?php foreach (($tax['layers'] ?? []) as $k => $lbl): ?><option value="<?= h($k) ?>" <?= $k === $values['layer'] ? 'selected' : '' ?>><?= h($lbl) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="editor-field">
              <label for="new-type">Typ</label>
              <select class="select" id="new-type" name="type">
                <?php foreach (($tax['types'] ?? []) as $k => $lbl): ?><option value="<?= h($k) ?>" <?= $k === $values['type'] ? 'selected' : '' ?>><?= h($lbl) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="editor-field">
              <label for="new-level">Nivå</label>
              <select class="select" id="new-level" name="level">
                <?php foreach (($tax['levels'] ?? [1, 2, 3]) as $lvl): ?><option value="<?= h((string)$lvl) ?>" <?= (string)$lvl === $values['level'] ? 'selected' : '' ?>><?= h((string)$lvl) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="editor-field">
              <label for="new-maturity">Mognad</label>
              <select class="select" id="new-maturity" name="maturity">
                <?php foreach ($maturityOptions as $v => $text): ?><option value="<?= h((string)$v) ?>" <?= (string)$v === $values['maturity'] ? 'selected' : '' ?>><?= h($text) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
        </fieldset>

        <fieldset class="editor-group editor-group--content">
          <legend>Innehåll</legend>
          <div class="editor-fields" style="margin-bottom:14px">
            <div class="editor-field editor-field--wide"><label for="new-url">Länk (valfri)</label><input class="input" id="new-url" name="url" value="<?= h($values['url']) ?>" type="url" placeholder="https://…"></div>
          </div>
          <div class="editor-field">
            <label for="new-body">Markdown</label>
            <textarea class="textarea" id="new-body" name="body" style="width:100%;height:30vh;min-height:200px"><?= h($values['body']) ?></textarea>
          </div>
        </fieldset>

        <?php foreach (['source_id', 'source_status'] as $key): ?><input type="hidden" name="<?= h($key) ?>" value="<?= h($values[$key]) ?>"><?php endforeach; ?>

        <div class="editor-footer-actions">
          <button class="btn btn--primary" type="submit">✓ Skapa förmåga</button>
          <a class="btn btn--ghost" href="<?= h($editorTarget) ?>">Avbryt</a>
        </div>
      </form>
    </section>
  </div>
</main>
<script defer src="<?= h(base_path('assets/new-capability.js') . '?v=' . filemtime(__DIR__ . '/../assets/new-capability.js')) ?>"></script>
</body>
</html>
