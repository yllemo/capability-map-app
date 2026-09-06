<?php
require __DIR__ . '/_auth.php';
require_auth();
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
      $text = App\CapabilityReference::markdown($referenceId, $selection, get_content_dirs());
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
$title = 'Ny förmåga';
$activeNav = 'editor';
ob_start();
?>
<div class="card" style="max-width:760px;margin:0 auto">
  <div class="card__hd"><strong>Skapa ny förmåga</strong><a class="btn btn--ghost" href="index.php?map=<?= h(rawurlencode($selectedKey)) ?>">← Till editor</a></div>
  <div class="card__bd">
    <?php if ($error): ?><p role="alert"><?= h($error) ?></p><?php endif; ?>
    <details style="margin-bottom:20px" <?= ($_POST['creation_mode'] ?? '') === 'reference' ? 'open' : '' ?>>
      <summary>Länka en befintlig förmåga (referenskort)</summary>
      <p class="muted">Välj originalet från någon av kartorna. Kortet följer originalets innehåll och skikt. Klick på kortet öppnar originalet. Endast en liten Markdown-fil med omstyrningen sparas i den aktuella kartan.</p>
      <form method="post" action="new.php?map=<?= h(rawurlencode($selectedKey)) ?>" class="grid" style="gap:10px">
        <?= csrf_field() ?><input type="hidden" name="creation_mode" value="reference">
        <label for="reference-target">Originalförmåga (karta · namn · ID)</label>
        <select class="select" id="reference-target" name="reference_target" required>
          <option value="">Välj förmåga…</option>
          <?php foreach (App\CapabilityReference::choices(get_content_dirs()) as $choice): ?>
          <option value="<?= h(json_encode([$choice['map'], $choice['id']], JSON_UNESCAPED_UNICODE)) ?>"><?= h($choice['label'] . ' · ' . $choice['name'] . ' · ' . $choice['id']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn--primary" type="submit">Skapa referenskort</button>
      </form>
    </details>
    <details style="margin-bottom:20px">
      <summary>Importera en förmåga från JSON</summary>
      <p class="muted">Samma format som Importera JSON. Välj en fil (högst 5 MB) och sedan en förmåga. Formuläret fylls i; inget sparas förrän du klickar på Skapa.</p>
      <form id="json-preview-form" enctype="multipart/form-data" action="preview_json.php" method="post">
        <?= csrf_field() ?>
        <label for="json-file">JSON-fil</label>
        <input class="input" type="file" id="json-file" name="json_file" accept=".json,application/json" required>
        <button class="btn btn--secondary" type="submit">Läs JSON</button>
      </form>
      <p id="json-import-message" role="status" aria-live="polite"></p>
      <div id="json-choice" hidden>
        <label for="json-capability">Välj förmåga</label>
        <select class="select" id="json-capability"></select>
        <p id="json-choice-description" class="muted"></p>
        <button class="btn btn--secondary" type="button" id="use-json-capability">Fyll i formuläret</button>
      </div>
    </details>
    <form id="new-capability-form" method="post" action="new.php?map=<?= h(rawurlencode($selectedKey)) ?>" class="grid" style="gap:12px">
      <?= csrf_field() ?>
      <div class="grid grid--2" style="gap:12px">
      <?php foreach (['id' => 'ID', 'name' => 'Namn', 'area' => 'Område', 'description' => 'Beskrivning'] as $key => $label): ?>
        <div><label for="new-<?= h($key) ?>"><?= h($label) ?></label><input class="input" id="new-<?= h($key) ?>" name="<?= h($key) ?>" value="<?= h($values[$key]) ?>" <?= in_array($key, ['id', 'name'], true) ? 'required' : '' ?>></div>
      <?php endforeach; ?>
      <?php foreach (['layer' => ['Skikt', $tax['layers'] ?? []], 'type' => ['Typ', $tax['types'] ?? []], 'level' => ['Nivå', array_combine($tax['levels'] ?? [1,2,3], $tax['levels'] ?? [1,2,3])], 'maturity' => ['Mognad', [1 => '1 · Initial', 2 => '2 · Under utveckling', 3 => '3 · Definierad', 4 => '4 · Hanterad', 5 => '5 · Optimerad']]] as $key => [$label, $options]): ?>
        <div><label for="new-<?= h($key) ?>"><?= h($label) ?></label><select class="select" id="new-<?= h($key) ?>" name="<?= h($key) ?>"><?php foreach ($options as $value => $text): ?><option value="<?= h((string)$value) ?>" <?= (string)$value === $values[$key] ? 'selected' : '' ?>><?= h((string)$text) ?></option><?php endforeach; ?></select></div>
      <?php endforeach; ?>
      </div>
      <label for="new-url">Länk (valfri)</label><input class="input" id="new-url" name="url" value="<?= h($values['url']) ?>" type="url">
      <label for="new-body">Markdown</label><textarea class="textarea" id="new-body" name="body" rows="10"><?= h($values['body']) ?></textarea>
      <?php foreach (['source_id', 'source_status'] as $key): ?><input type="hidden" name="<?= h($key) ?>" value="<?= h($values[$key]) ?>"><?php endforeach; ?>
      <button class="btn btn--primary" type="submit">Skapa</button>
    </form>
  </div>
</div>
<script defer src="<?= h(base_path('assets/new-capability.js')) ?>"></script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../app/templates/layout.php';
