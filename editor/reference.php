<?php
require __DIR__ . '/_auth.php';
require_edit();
header('Content-Type: text/html; charset=UTF-8');
$selectedKey = get_selected_content_key();
$dirs = readable_content_dirs();
$error = '';
$notice = '';
try {
  $rel = $_GET['file'] ?? '';
  if ($rel === '' && is_string($_GET['id'] ?? null)) {
    $record = (new App\CapabilityRepository(get_content_dir()))->rawById($_GET['id']);
    if ($record) $rel = ltrim(substr(str_replace('\\', '/', $record['cap']->path), strlen(rtrim(str_replace('\\', '/', get_content_dir()), '/'))), '/');
  }
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
    $input = $_POST['reference_options'] ?? [];
    if (!is_array($input)) throw new RuntimeException('Ogiltiga kortinställningar.');
    $overrides = App\CapabilityReference::overrides($input, cfg('taxonomy'));
    $text = App\CapabilityReference::markdown($meta['id'], $selection, $dirs, $overrides);
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
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Referenskort · <?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?></title>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/admin.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/reference.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#reference-form">Hoppa till kortinställningarna</a>
<header class="site-header">
  <a class="brand admin-brand" href="<?= h(base_path('view/overview.php?map=' . rawurlencode($selectedKey))) ?>"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong><?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?></strong><span>Referenskort</span></div></a>
  <nav aria-label="Vyer och verktyg"><a href="<?= h(base_path('editor/index.php?map=' . rawurlencode($selectedKey))) ?>">Till editor</a><?php if (App\Auth::isAdministrator()): ?><a href="<?= h(base_path('admin/')) ?>">Admin</a><?php endif; ?><button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema">◐</button></nav>
</header>
<main class="admin-main reference-main" id="reference-form">
<section class="intro"><div><p class="eyebrow">KORTINSTÄLLNINGAR · <?= h($dirs[$selectedKey]['label'] ?? $selectedKey) ?></p><h1>Referenskort</h1><p>Välj originalförmåga och anpassa kortets placering och nivåer.</p></div></section>
<?php if ($error): ?><p class="admin-notice is-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if ($notice): ?><p class="admin-notice" role="status"><?= h($notice) ?></p><?php endif; ?>
<?php if (isset($meta['redirect_map'])): ?>
<section class="card"><div class="card__bd"><h2>Länkad förmåga</h2>
  <p>Innehållet hämtas från originalet. Att ta bort referensen påverkar inte originalförmågan.</p>
  <?php try { $target = App\CapabilityReference::resolve($meta, $dirs); ?>
    <p><a class="btn btn--secondary" href="<?= h(base_path('view/capability.php?map=' . rawurlencode($target['map']) . '&id=' . rawurlencode($target['cap']->id))) ?>">Öppna original: <?= h($target['cap']->name) ?></a></p>
  <?php } catch (RuntimeException $e) { ?><p role="alert"><?= h($e->getMessage()) ?> Välj ett nytt mål nedan.</p><?php } ?>
  <form method="post" action="reference.php?map=<?= h(rawurlencode($selectedKey)) ?>&amp;file=<?= h(rawurlencode($rel)) ?>" class="grid" style="gap:12px">
    <?= csrf_field() ?><label for="reference-target">Originalförmåga</label>
    <select class="select" id="reference-target" name="reference_target" required><option value="">Välj mål…</option>
      <?php foreach (App\CapabilityReference::choices($dirs) as $choice): ?><option value="<?= h(json_encode([$choice['map'], $choice['id']], JSON_UNESCAPED_UNICODE)) ?>" <?= ($meta['redirect_map'] === $choice['map'] && ($meta['redirect_id'] ?? '') === $choice['id']) ? 'selected' : '' ?>><?= h($choice['label'] . ' · ' . $choice['name'] . ' · ' . $choice['id']) ?></option><?php endforeach; ?>
    </select>
    <?php $referenceValues = $meta; require __DIR__ . '/../app/templates/reference_options.php'; ?>
    <button class="btn btn--primary" type="submit">Spara referenskort</button>
  </form>
</div></section>
  <details class="card"><summary class="reference-summary">Visa Markdown och omstyrning</summary><div class="card__bd"><pre class="reference-source"><?= h($raw) ?></pre></div></details>
  <section class="card"><div class="card__bd"><h2>Ta bort referenskort</h2><p>Endast detta referenskort tas bort. Originalförmågan finns kvar.</p>
  <form method="post" action="delete.php?map=<?= h(rawurlencode($selectedKey)) ?>" onsubmit="return confirm('Ta bort referenskortet? Originalet påverkas inte.');">
    <?= csrf_field() ?><input type="hidden" name="file" value="<?= h($rel) ?>"><button class="btn btn--danger" type="submit">Ta bort referens</button>
  </form>
</div></section>
<?php endif; ?>
</main></body></html>
