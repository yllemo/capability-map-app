<?php
require __DIR__ . '/../app/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;
use App\Markdown;

$app = cfg('app');
$tax = cfg('taxonomy');

$id = trim($_GET['id'] ?? '');
if ($id === '') { http_response_code(400); echo 'Missing id'; exit; }

// Use selected content directory
$contentDir = get_content_dir();
$repo = new CapabilityRepository($contentDir);
$data = $repo->byId($id);

if (!$data) { http_response_code(404); echo 'Not found'; exit; }

$cap = $data['cap'];
$body = (string)($data['body'] ?? '');
$bodyHtml = Markdown::toHtml($body);

// Calculate relative path from content_dir for editor link
$relPath = '';
if ($cap->path && str_starts_with($cap->path, $app['content_dir'])) {
  $relPath = ltrim(str_replace($app['content_dir'], '', $cap->path), DIRECTORY_SEPARATOR);
  $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
}

function metaRow(string $k, $v): string {
  if ($v === null || $v === '' || $v === []) return '';
  if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  return '<div class="flex items-center justify-between gap-4 py-2 border-b border-gray-100 dark:border-neutral-800"><div class="text-xs text-gray-500 dark:text-neutral-400">'.h($k).'</div><div class="text-xs font-mono text-gray-700 dark:text-neutral-200">'.h((string)$v).'</div></div>';
}

$meta = $cap->meta;

?><!doctype html>
<html lang="sv" class="antialiased">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($cap->name) ?> – Förmåga</title>
  <link rel="icon" href="<?= h(base_path('assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="icon" href="<?= h(base_path('assets/favicon.png')) ?>" type="image/png">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { inera:{ blue:'#005595', dark:'#003e6d', light:'#e6f0f8' } } } } }
  </script>
  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body class="bg-slate-50 dark:bg-neutral-950 text-slate-800 dark:text-neutral-100 min-h-screen">

<header class="bg-white dark:bg-neutral-950/80 border-b border-gray-200 dark:border-neutral-800 md:sticky md:top-0 z-50 shadow-sm backdrop-blur">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <div class="bg-inera-blue w-10 h-10 rounded flex items-center justify-center text-white font-bold shadow-sm">EA</div>
        <div>
          <div class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wider"><?= h($tax['layers'][$cap->layer] ?? $cap->layer) ?> • <?= h($cap->area) ?></div>
          <h1 class="text-xl font-bold text-gray-900 dark:text-neutral-50 leading-tight"><?= h($cap->name) ?></h1>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a class="px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
           href="<?= h(base_path('view/index.php')) ?>">← Karta</a>
        <?php if ($relPath): ?>
          <a class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
             href="<?= h(base_path('editor/index.php?file=' . rawurlencode($relPath))) ?>">
            <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Redigera
          </a>
        <?php endif; ?>
        <a href="<?= h(base_path('view/help.php')) ?>"
           class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
           title="Hjälp & Best Practices">
          <svg class="h-5 w-5 text-gray-600 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </a>
        <button class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
                type="button" data-theme-toggle aria-label="Växla tema">🌓</button>
      </div>
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <article class="lg:col-span-2 bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl shadow-sm p-6">
      <?php if ($cap->description): ?>
        <p class="text-sm text-gray-600 dark:text-neutral-300 mb-4"><?= h($cap->description) ?></p>
      <?php endif; ?>
      <div class="prose" style="max-width: none;">
        <?= $bodyHtml ?>
      </div>
    </article>

    <aside class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl shadow-sm p-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-bold text-gray-900 dark:text-neutral-100">Metadata</h2>
        <span class="text-[10px] font-mono text-gray-500 dark:text-neutral-400"><?= h($cap->id) ?></span>
      </div>
      <?= metaRow('layer', $tax['layers'][$meta['layer']] ?? ($meta['layer'] ?? '')) ?>
      <?= metaRow('area', $meta['area'] ?? '') ?>
      <?= metaRow('level', $meta['level'] ?? '') ?>
      <?= metaRow('type', $tax['types'][$meta['type']] ?? ($meta['type'] ?? '')) ?>
      <?= metaRow('owner', $meta['owner'] ?? '') ?>
      <?= metaRow('status', $meta['status'] ?? '') ?>
      <?= metaRow('maturity', $meta['maturity'] ?? '') ?>
      <?= metaRow('criticality', $meta['criticality'] ?? '') ?>
      <?= metaRow('risk_level', $meta['risk_level'] ?? '') ?>
      <?= metaRow('tags', $meta['tags'] ?? '') ?>
      <?= metaRow('updated', $meta['updated'] ?? '') ?>
    </aside>
  </div>
</main>

</body>
</html>
