<?php
require __DIR__ . '/../app/bootstrap.php';
$selfView = 'view/capability_new.php';
$mapInterface = 'new';
$mapInterfaceTargets = ['classic' => 'view/capability.php', 'new' => 'view/capability_new.php'];
require __DIR__ . '/../app/map_interface.php';
$backMapTarget = $mapInterface === 'new' ? 'view/overview.php' : 'view/index.php';
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;
use App\Markdown;

$app = cfg('app');
$tax = cfg('taxonomy');
$uiCfg = cfg('ui');

$id = trim($_GET['id'] ?? '');
if ($id === '') { http_response_code(400); echo 'Missing id'; exit; }

// Use selected content directory
$selectedKey = get_selected_content_key();
$contentDir = get_content_dir();
$repo = new CapabilityRepository($contentDir);
$data = $repo->byId($id);

if (!$data) {
  // Fallback: try all readable content folders if map is missing/wrong.
  $dirs = readable_content_dirs();
  foreach ($dirs as $key => $dirInfo) {
    if ($key === $selectedKey) continue;
    $candidateRepo = new CapabilityRepository((string)$dirInfo['path']);
    $candidate = $candidateRepo->byId($id);
    if (!$candidate) continue;

    // Persist the found folder and canonicalize URL with map param.
    set_content_dir($key);
    $selectedKey = $key;
    $contentDir = (string)$dirInfo['path'];
    $data = $candidate;

    $target = base_path($selfView . '?id=' . rawurlencode($id) . '&map=' . rawurlencode($key));
    if (($_GET['download'] ?? '') === 'md') $target .= '&download=md';
    header('Location: ' . $target, true, 302);
    exit;
  }

  http_response_code(404);
  echo 'Not found';
  exit;
}

require_read($selectedKey);

$cap = $data['cap'];
if (isset($cap->meta['redirect_map'])) {
  try {
    $target = App\CapabilityReference::resolve($cap->meta, get_content_dirs());
    $url = base_path($selfView . '?id=' . rawurlencode($target['cap']->id) . '&map=' . rawurlencode($target['map']));
    if (($_GET['download'] ?? '') === 'md') $url .= '&download=md';
    header('Location: ' . $url, true, 302);
  } catch (RuntimeException $e) {
    http_response_code(404);
    echo h($e->getMessage());
  }
  exit;
}
if (($_GET['download'] ?? '') === 'md') {
  $content = file_get_contents($cap->path);
  if ($content === false) {
    http_response_code(500);
    echo 'Failed to read file';
    exit;
  }

  // Note where this file came from, right after the frontmatter so the file
  // still parses correctly if it's re-imported elsewhere.
  $sourceUrl = absolute_url($selfView . '?id=' . rawurlencode($cap->id) . '&map=' . rawurlencode($selectedKey));
  $exportDate = date('Y-m-d');
  $sourceComment = "<!-- Exporterad från Förmågekarta: {$sourceUrl} ({$exportDate}) -->\n";
  $content = preg_match('/^(---\R.*?\R---\R)(.*)$/s', $content, $m)
    ? $m[1] . $sourceComment . $m[2]
    : $sourceComment . $content;

  $filename = pathinfo($cap->path, PATHINFO_FILENAME) . '.md';
  $fallbackFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
  header('Content-Type: text/markdown; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $fallbackFilename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
  header('Content-Length: ' . strlen($content));
  header('Cache-Control: no-store');
  echo $content;
  exit;
}

$body = (string)($data['body'] ?? '');
$bodyHtml = Markdown::toHtml($body);
$hasMermaid = str_contains($bodyHtml, 'class="mermaid"');

// Calculate relative path from currently selected content directory for editor link
$relPath = '';
if (!empty($cap->path)) {
  $capPathNorm = str_replace('\\', '/', (string)$cap->path);
  $contentDirNorm = rtrim(str_replace('\\', '/', (string)$contentDir), '/');
  $prefix = $contentDirNorm . '/';

  if (str_starts_with($capPathNorm, $prefix)) {
    $relPath = substr($capPathNorm, strlen($prefix));
  }
}

function metaRow(string $k, $v): string {
  if ($v === null || $v === '' || $v === []) return '';
  if (is_array($v)) $v = implode(', ', array_map('strval', $v));
  return '<div class="meta-row"><dt>' . h($k) . '</dt><dd>' . h((string)$v) . '</dd></div>';
}

$meta = $cap->meta;
$m = (int)$cap->get('maturity', 0);
if ($m < 1 || $m > 5) $m = 0;
$maturityLabels = [0 => 'Ej bedömd', 1 => 'Initial', 2 => 'Under utveckling', 3 => 'Definierad', 4 => 'Hanterad', 5 => 'Optimerad'];
$tags = $meta['tags'] ?? [];
if (!is_array($tags)) $tags = [$tags];
$tags = array_filter($tags, fn($t) => is_scalar($t) && trim((string)$t) !== '');

?><!doctype html>
<html lang="sv">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($cap->name) ?> – <?= h($uiCfg['title'] ?? 'Förmågekarta') ?></title>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/interface-toggle.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/capability-view.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#cap-content">Hoppa till innehållet</a>
<header class="site-header">
  <a href="<?= h(base_path($backMapTarget)) ?>" class="brand" style="text-decoration:none;color:inherit">
    <span class="brand-symbol" aria-hidden="true">▦</span>
    <div><strong><?= h($uiCfg['title'] ?? 'Förmågekarta') ?></strong><span>Förmågedetaljer</span></div>
  </a>
  <nav aria-label="Verktyg">
    <a href="<?= h(base_path($backMapTarget . '?map=' . rawurlencode($selectedKey))) ?>">← Karta</a>
    <?php if ($relPath): ?>
      <a href="<?= h(base_path('editor/index.php?file=' . rawurlencode($relPath) . '&map=' . rawurlencode($selectedKey))) ?>">Redigera</a>
    <?php endif; ?>
    <a href="<?= h(base_path($selfView . '?id=' . rawurlencode($id) . '&map=' . rawurlencode($selectedKey) . '&download=md')) ?>"
       title="Ladda ner förmågan som Markdown (.md)">Ladda ner .md</a>
    <?php require __DIR__ . '/../app/templates/interface_toggle.php'; ?>
    <a href="<?= h(base_path('view/help.php')) ?>">Hjälp</a>
    <?php if (current_user() !== null): ?>
      <span class="header-user" title="Inloggad som <?= h(user_display_name()) ?>"><?= h(user_display_name()) ?></span>
      <a href="<?= h(base_path('editor/logout.php')) ?>">Logga ut</a>
    <?php elseif (!App\Auth::isOpen()): ?>
      <a href="<?= h(base_path('editor/login.php')) ?>">Logga in</a>
    <?php endif; ?>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main id="cap-content">
  <section class="intro" aria-labelledby="page-title">
    <div>
      <p class="eyebrow"><?= h(mb_strtoupper($tax['layers'][$cap->layer] ?? $cap->layer ?? '')) ?><?php if ($cap->area): ?> <span><?= h($cap->area) ?></span><?php endif; ?></p>
      <h1 id="page-title"><?= h($cap->name) ?></h1>
      <?php if ($cap->description): ?><p><?= h($cap->description) ?></p><?php endif; ?>
    </div>
  </section>

  <div class="cap-layout">
    <article class="cap-article">
      <?php if (isset($cap->meta['redirect_map']) && is_authed()): ?><p class="overview-card-context">↗ Länkad förmåga</p><?php endif; ?>
      <div class="prose" style="max-width:none">
        <?= $bodyHtml ?>
      </div>
    </article>

    <aside class="cap-aside">
      <div class="cap-aside-head">
        <h2>Metadata</h2>
        <span class="cap-id-badge"><?= h($cap->id) ?></span>
      </div>
      <div class="cap-maturity-row"><span class="status-badge maturity-<?= $m ?>"><i aria-hidden="true"></i><?= h($maturityLabels[$m]) ?></span></div>
      <dl class="meta-list">
        <?= metaRow('Skikt', $tax['layers'][$meta['layer']] ?? ($meta['layer'] ?? '')) ?>
        <?= metaRow('Område', $meta['area'] ?? '') ?>
        <?= metaRow('Nivå', $meta['level'] ?? '') ?>
        <?= metaRow('Typ', $tax['types'][$meta['type']] ?? ($meta['type'] ?? '')) ?>
        <?= metaRow('Ansvarig', $meta['owner'] ?? '') ?>
        <?= metaRow('Status', $meta['status'] ?? '') ?>
        <?= metaRow('Kritikalitet', $meta['criticality'] ?? '') ?>
        <?= metaRow('Risk', $meta['risk_level'] ?? '') ?>
        <?= metaRow('Uppdaterad', $meta['updated'] ?? '') ?>
      </dl>
      <?php if ($tags): ?>
        <div class="overview-card-tags" aria-label="Taggar">
          <?php foreach ($tags as $t): ?><a href="<?= h(base_path('view/tags.php?tag=' . rawurlencode(trim((string)$t)))) ?>" title="<?= h('Visa förmågor med taggen ' . trim((string)$t)) ?>"><?= h((string)$t) ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </aside>
  </div>
</main>
<?php if ($hasMermaid): ?>
<script type="module">
(async () => {
  const nodes = [...document.querySelectorAll('pre.mermaid')];
  if (!nodes.length) return;
  nodes.forEach(n => { n.dataset.mermaidSrc = n.textContent; });

  let mermaid;
  try {
    ({ default: mermaid } = await import('https://cdn.jsdelivr.net/npm/mermaid@latest/dist/mermaid.esm.min.mjs'));
  } catch (err) {
    console.error('Kunde inte ladda Mermaid:', err);
    return;
  }

  const isDark = () => document.documentElement.dataset.theme === 'dark';
  const cssVar = (name, fallback) => {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
  };

  // Mirror the page's own design tokens into Mermaid's theme instead of its
  // stock gray palette, so diagrams look bright/on-brand in light mode and
  // sit naturally on the dark background in dark mode.
  function themeVariables() {
    const dark = isDark();
    const blue = cssVar('--blue', dark ? '#86c6fa' : '#145d91');
    const surface = cssVar('--surface', dark ? '#16232f' : '#ffffff');
    const soft = cssVar('--soft', dark ? '#1b3247' : '#eef5fb');
    const line = cssVar('--line', dark ? '#2c4257' : '#d7e3ee');
    const text = cssVar('--text', dark ? '#e6edf5' : '#172b42');
    const lineColor = dark ? '#7fa9c4' : '#6f89a3';
    return {
      background: surface,
      fontFamily: 'system-ui, -apple-system, "Segoe UI", sans-serif',
      primaryColor: soft,
      primaryTextColor: text,
      primaryBorderColor: blue,
      lineColor,
      secondaryColor: surface,
      secondaryTextColor: text,
      secondaryBorderColor: line,
      tertiaryColor: surface,
      tertiaryTextColor: text,
      tertiaryBorderColor: line,
      textColor: text,
      mainBkg: soft,
      nodeBorder: blue,
      clusterBkg: surface,
      clusterBorder: line,
      titleColor: text,
      edgeLabelBackground: surface,
      noteBkgColor: soft,
      noteTextColor: text,
      noteBorderColor: blue,
      actorBkg: soft,
      actorBorder: blue,
      actorTextColor: text,
      actorLineColor: lineColor,
      signalColor: text,
      signalTextColor: text,
      labelBoxBkgColor: soft,
      labelBoxBorderColor: blue,
      labelTextColor: text,
      loopTextColor: text,
      activationBkgColor: surface,
      activationBorderColor: blue,
      sequenceNumberColor: dark ? '#0b1620' : '#ffffff',
    };
  }

  async function render() {
    mermaid.initialize({ startOnLoad: false, theme: 'base', themeVariables: themeVariables(), securityLevel: 'strict' });
    nodes.forEach(n => {
      n.removeAttribute('data-processed');
      n.textContent = n.dataset.mermaidSrc;
    });
    try {
      await mermaid.run({ nodes });
    } catch (err) {
      console.error('Kunde inte rendera Mermaid-diagram:', err);
    }
  }

  await render();
  document.addEventListener('click', event => {
    if (event.target.closest('[data-theme-toggle]')) setTimeout(render, 0);
  });
})();
</script>
<?php endif; ?>
</body>
</html>
