<?php
/** @var string $title */
/** @var string $content */
/** @var string $activeNav view|editor */
/** @var array $extraHead */
/** @var string $containerClass */
$site = cfg('app')['site_name'] ?? 'Capability Maps';
$extraHead = $extraHead ?? [];
$containerClass = $containerClass ?? 'container';
?><!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> – <?= h($site) ?></title>
  <link rel="stylesheet" href="<?= h(base_path('assets/app.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
  <?php foreach ($extraHead as $tag) echo $tag . "\n"; ?>
</head>
<body>
<header class="topbar">
  <div class="brand">
    <a href="<?= h(base_path('view/index.php')) ?>" class="brand__link"><?= h($site) ?></a>
  </div>

  <form class="topbar__search" action="<?= h(base_path('view/index.php')) ?>" method="get">
    <input name="q" class="input" placeholder="Sök förmågor…" value="<?= h($_GET['q'] ?? '') ?>">
  </form>

  <div class="topbar__actions">
    <a class="btn btn--ghost" href="<?= h(base_path('view/help.php')) ?>" title="Hjälp & Best Practices">
      <svg style="width:18px;height:18px" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </a>
    <button class="btn btn--ghost" type="button" data-theme-toggle aria-label="Växla tema">🌓</button>
    <a class="btn btn--ghost <?= ($activeNav==='view'?'is-active':'') ?>" href="<?= h(base_path('view/index.php')) ?>">Viewer</a>
    <a class="btn btn--ghost <?= ($activeNav==='editor'?'is-active':'') ?>" href="<?= h(base_path('editor/index.php')) ?>">Editor</a>
  </div>
</header>

<main class="<?= h($containerClass) ?>">
  <?= $content ?>
</main>

<?php if (($activeNav ?? '') !== 'editor'): ?>
<footer class="footer">
  <div class="muted">Driven av Markdown i <code>/content</code> • PHP-only • Dark/Light mode</div>
</footer>
<?php endif; ?>
</body>
</html>
