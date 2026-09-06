<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');
$selectedKey = get_selected_content_key();
$dirs = get_content_dirs();
$error = '';
$notice = '';
try {
  $rel = $_GET['file'] ?? '';
  if (!is_string($rel) || $rel === '') throw new RuntimeException('Välj en referensfil.');
  $abs = App\PathGuard::safeJoin(get_content_dir(), $rel);
  if (!is_file($abs)) throw new RuntimeException('Referensfilen saknas.');
  $raw = file_get_contents($abs);
  if ($raw === false) throw new RuntimeException('Kunde inte läsa referensen.');
  $meta = App\Frontmatter::parse($raw)['meta'];
  if (!isset($meta['redirect_map']) || !is_string($meta['id'] ?? null)) throw new RuntimeException('Detta är inte en referensfil.');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !csrf_verify($token)) throw new RuntimeException('Ladda om sidan och försök igen.');
    $selection = $_POST['reference_target'] ?? '';
    if (!is_string($selection)) throw new RuntimeException('Välj ett mål.');
    $text = App\CapabilityReference::markdown($meta['id'], $selection, $dirs);
    // Keep the old file intact if writing the replacement fails.
    $temp = $abs . '.' . bin2hex(random_bytes(8)) . '.tmp';
    if (@file_put_contents($temp, $text, LOCK_EX) !== strlen($text) || !@rename($temp, $abs)) {
      @unlink($temp);
      throw new RuntimeException('Kunde inte spara referensen.');
    }
    $meta = App\Frontmatter::parse($text)['meta'];
    $raw = $text;
    $notice = 'Referensen sparades.';
  }
} catch (Throwable $e) { $error = $e->getMessage(); }
$title = 'Referenskort';
$activeNav = 'editor';
ob_start();
?>
<div class="card" style="max-width:760px;margin:0 auto"><div class="card__hd"><strong>Referenskort</strong><a class="btn btn--ghost" href="index.php?map=<?= h(rawurlencode($selectedKey)) ?>">Till editor</a></div><div class="card__bd">
<?php if ($error): ?><p role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p role="status"><?= h($notice) ?></p><?php endif; ?>
<?php if (isset($meta['redirect_map'])): ?>
  <p>Innehållet hämtas från originalet. Att ta bort referensen påverkar inte originalförmågan.</p>
  <?php try { $target = App\CapabilityReference::resolve($meta, $dirs); ?>
    <p><a class="btn btn--secondary" href="<?= h(base_path('view/capability.php?map=' . rawurlencode($target['map']) . '&id=' . rawurlencode($target['cap']->id))) ?>">Öppna original: <?= h($target['cap']->name) ?></a></p>
  <?php } catch (RuntimeException $e) { ?><p role="alert"><?= h($e->getMessage()) ?> Välj ett nytt mål nedan.</p><?php } ?>
  <form method="post" class="grid" style="gap:12px">
    <?= csrf_field() ?><label for="reference-target">Originalförmåga</label>
    <select class="select" id="reference-target" name="reference_target" required><option value="">Välj mål…</option>
      <?php foreach (App\CapabilityReference::choices($dirs) as $choice): ?><option value="<?= h(json_encode([$choice['map'], $choice['id']], JSON_UNESCAPED_UNICODE)) ?>" <?= ($meta['redirect_map'] === $choice['map'] && ($meta['redirect_id'] ?? '') === $choice['id']) ? 'selected' : '' ?>><?= h($choice['label'] . ' · ' . $choice['name'] . ' · ' . $choice['id']) ?></option><?php endforeach; ?>
    </select><button class="btn btn--primary" type="submit">Spara mål</button>
  </form>
  <pre style="overflow:auto"><?= h($raw) ?></pre>
  <form method="post" action="delete.php?map=<?= h(rawurlencode($selectedKey)) ?>" onsubmit="return confirm('Ta bort referenskortet? Originalet påverkas inte.');">
    <?= csrf_field() ?><input type="hidden" name="file" value="<?= h($rel) ?>"><button class="btn btn--danger" type="submit">Ta bort referens</button>
  </form>
<?php endif; ?>
</div></div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../app/templates/layout.php';
