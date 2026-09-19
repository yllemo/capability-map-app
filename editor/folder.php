<?php
require __DIR__ . '/_auth.php';
require_edit();
header('Content-Type: text/html; charset=UTF-8');
$key = get_selected_content_key();
$dirs = get_content_dirs();
$entry = $dirs[$key];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    foreach (['csrf_token', 'action', 'label', 'folder', 'confirmation'] as $field) if (!is_string($_POST[$field] ?? '')) throw new RuntimeException('Ogiltiga formulärvärden.');
    if (!csrf_verify($_POST['csrf_token'] ?? '')) throw new RuntimeException('Ladda om sidan och försök igen.');
    $action = $_POST['action'] ?? '';
    App\ContentFolders::manage(dirname(__DIR__), $key, $action, $_POST);
    $next = $key;
    if ($action === 'delete') {
      unset($dirs[$key]);
      $next = (string)array_key_first($dirs);
    }
    header('Location: index.php?map=' . rawurlencode($next));
    exit;
  } catch (Throwable $e) { $error = $e->getMessage(); }
}
$title = 'Hantera folder';
$activeNav = 'editor';
ob_start();
?>
<div class="card" style="max-width:760px;margin:0 auto"><div class="card__hd"><strong>Hantera <?= h($entry['label'] ?? $key) ?></strong><a class="btn btn--ghost" href="index.php?map=<?= h(rawurlencode($key)) ?>">Till editor</a></div><div class="card__bd">
<?php if ($error): ?><p role="alert"><?= h($error) ?></p><?php endif; ?>
<h2>Byt namn</h2>
<p class="muted">Kartans interna nyckel behålls så att länkar och referenskort fortsätter fungera. För en monterad volym kan du ändra enbart visningsnamnet.</p>
<form method="post" class="grid" style="gap:10px">
  <?= csrf_field() ?><input type="hidden" name="action" value="rename">
  <label>Visningsnamn<input class="input" name="label" value="<?= h($entry['label'] ?? $key) ?>" required></label>
  <label>Katalognamn under /content<input class="input" name="folder" value="<?= h($entry['folder'] ?? basename($entry['path'])) ?>" pattern="[a-zA-Z0-9_-]+" required></label>
  <button class="btn btn--primary" type="submit">Spara namn</button>
</form>
<hr style="margin:28px 0">
<h2>Ta bort folder och allt innehåll</h2>
<p><strong>Alla filer och undermappar i denna karta raderas permanent.</strong> Referenskort i andra kartor som pekar hit får ett saknat mål. Original i andra kartor som denna karta länkar till påverkas inte.</p>
<p>Vi rekommenderar att du laddar ner och kontrollerar en fullständig backup först.</p>
<p><a class="btn btn--secondary" href="download_zip.php?key=<?= h(rawurlencode($key)) ?>&amp;full=1">Ladda ner fullständig ZIP-backup</a></p>
<p class="muted">Monterade kataloger lämnas kvar tomma om de inte kan tas bort. Arbeta utan andra samtidiga redigeringar under raderingen.</p>
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <label>Skriv <strong><?= h($entry['label'] ?? $key) ?></strong> för att bekräfta:<input class="input" name="confirmation" autocomplete="off" required></label>
  <p><button class="btn btn--danger" type="submit">Radera folder och alla filer</button></p>
</form>
</div></div>
<?php $content = ob_get_clean(); require __DIR__ . '/../app/templates/layout.php';
