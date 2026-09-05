<?php
require __DIR__ . '/../app/bootstrap.php';
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
  // Fallback: try all configured content folders if map is missing/wrong.
  $dirs = get_content_dirs();
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

    $target = base_path('view/capability.php?id=' . rawurlencode($id) . '&map=' . rawurlencode($key));
    if (($_GET['download'] ?? '') === 'md') $target .= '&download=md';
    header('Location: ' . $target, true, 302);
    exit;
  }

  http_response_code(404);
  echo 'Not found';
  exit;
}

$cap = $data['cap'];
if (($_GET['download'] ?? '') === 'md') {
  $content = file_get_contents($cap->path);
  if ($content === false) {
    http_response_code(500);
    echo 'Failed to read file';
    exit;
  }

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

/**
 * Get logo HTML based on UI configuration
 */
function getLogoHtml(array $uiCfg): string {
  $logoConfig = $uiCfg['logo'] ?? [];
  $svgFile = $logoConfig['svg_file'] ?? null;
  
  if ($svgFile) {
    $svgPath = __DIR__ . '/../config/' . $svgFile;
    if (file_exists($svgPath)) {
      $svgContent = file_get_contents($svgPath);
      // Add width and height attributes if they don't exist
      $width = $logoConfig['svg_width'] ?? '40';
      $height = $logoConfig['svg_height'] ?? '40';
      
      if (strpos($svgContent, 'width=') === false) {
        $svgContent = str_replace('<svg', '<svg width="' . $width . '" height="' . $height . '"', $svgContent);
      }
      
      return '<div class="flex items-center justify-center">' . $svgContent . '</div>';
    }
  }
  
  // Fallback to text logo
  $containerClasses = $logoConfig['container_classes'] ?? 'bg-inera-blue w-10 h-10 rounded flex items-center justify-center text-white font-bold shadow-sm';
  $fallbackText = $logoConfig['fallback_text'] ?? 'EA';
  
  return '<div class="' . $containerClasses . '">' . h($fallbackText) . '</div>';
}

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
  if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  return '<div class="flex items-center justify-between gap-4 py-2 border-b border-gray-100 dark:border-neutral-800"><div class="text-xs text-gray-500 dark:text-neutral-400">'.h($k).'</div><div class="text-xs font-mono text-gray-700 dark:text-neutral-200">'.h((string)$v).'</div></div>';
}

$meta = $cap->meta;

?><!doctype html>
<html lang="sv" class="antialiased">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($cap->name) ?> – <?= h($uiCfg['title'] ?? 'Förmågekarta') ?></title>

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
        <a href="<?= h(base_path('view/index.php')) ?>" class="flex items-center gap-3 no-underline hover:no-underline text-inherit">
          <?= getLogoHtml($uiCfg) ?>
          <div>
            <div class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wider"><?= h($tax['layers'][$cap->layer] ?? $cap->layer) ?> • <?= h($cap->area) ?></div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-neutral-50 leading-tight hover:text-gray-700 dark:hover:text-neutral-200 transition-colors"><?= h($cap->name) ?></h1>
          </div>
        </a>
      </div>
      <div class="flex items-center gap-2">
        <a class="px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
           href="<?= h(base_path('view/index.php?map=' . rawurlencode($selectedKey))) ?>">← Karta</a>
        <?php if ($relPath): ?>
          <a class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
             href="<?= h(base_path('editor/index.php?file=' . rawurlencode($relPath) . '&map=' . rawurlencode($selectedKey))) ?>">
            <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Redigera
          </a>
        <?php endif; ?>
        <a href="<?= h(base_path('view/capability.php?id=' . rawurlencode($id) . '&map=' . rawurlencode($selectedKey) . '&download=md')) ?>"
           class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
           title="Ladda ner förmågan som Markdown (.md)" aria-label="Ladda ner förmågan som Markdown (.md)">
          <svg class="h-5 w-5 text-gray-600 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m-4-4 4 4 4-4M5 16v4a1 1 0 001 1h12a1 1 0 001-1v-4"/>
          </svg>
        </a>
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
