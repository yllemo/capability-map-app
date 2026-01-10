<?php
require __DIR__ . '/../app/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');

use App\CapabilityRepository;

$app = cfg('app');
$tax = cfg('taxonomy');
$viewCfg = cfg('view');
$uiCfg = cfg('ui');

// Get available content directories and selected one
$contentDirs = get_content_dirs();
$selectedKey = get_selected_content_key();
$selectedDir = get_content_dir();

$repo = new CapabilityRepository($selectedDir);
$caps = $repo->all();

// Group by layer -> area
$groups = [];
foreach ($caps as $c) {
  $layer = $c->layer ?: 'unknown';
  $area = $c->area ?: 'Övrigt';
  $groups[$layer][$area][] = $c;
}
foreach ($groups as $layer => $areas) {
  ksort($groups[$layer]);
  foreach ($groups[$layer] as $area => $list) {
    usort($groups[$layer][$area], fn($a,$b) => strcmp($a->name, $b->name));
  }
}

$heatField = $viewCfg['heat_field'] ?? 'maturity';

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

function maturityColorClass(int $m): string {
  // Strong in light mode, slightly lighter (higher contrast) in dark mode
  return match(true){
    $m <= 1 => 'border-l-red-500 dark:border-l-red-400',
    $m == 2 => 'border-l-orange-500 dark:border-l-orange-400',
    $m == 3 => 'border-l-yellow-400 dark:border-l-yellow-300',
    $m == 4 => 'border-l-lime-500 dark:border-l-lime-400',
    default => 'border-l-green-500 dark:border-l-green-400',
  };
}


function sectionChrome(string $layerKey, array $tax): array {
  // Get display name from configuration, fallback to original layer label
  $displayName = $tax['layer_display_names'][$layerKey] ?? $tax['layers'][$layerKey] ?? ucfirst($layerKey);
  
  // return [sectionTitle, wrapperClasses]
  return match($layerKey){
    'ledning_styrning' => [$displayName, 'bg-indigo-50/60 dark:bg-neutral-900/55 border-indigo-100 dark:border-neutral-800'],
    'karnprocesser'    => [$displayName, 'bg-[rgba(230,240,248,.40)] dark:bg-neutral-900/55 border-blue-100 dark:border-blue-900/40 ring-1 ring-blue-50 dark:ring-neutral-900/30'],
    'verksamhetsstod'  => [$displayName, 'bg-gray-100/90 dark:bg-neutral-900/55 border-gray-200 dark:border-neutral-800'],
    default            => [$displayName, 'bg-gray-100/70 dark:bg-neutral-900/55 border-gray-200 dark:border-neutral-800'],
  };
}

?><!DOCTYPE html>
<html lang="sv" class="antialiased">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($uiCfg['page_title'] ?? 'Förmågekarta') ?></title>
  <link rel="icon" href="<?= h(base_path($uiCfg['favicon']['svg'] ?? 'assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="icon" href="<?= h(base_path($uiCfg['favicon']['png'] ?? 'assets/favicon.png')) ?>" type="image/png">


  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            inera: { blue:'#005595', dark:'#003e6d', light:'#e6f0f8' },
            maturity: { 1:'#ef4444', 2:'#f97316', 3:'#eab308', 4:'#84cc16', 5:'#22c55e' }
          }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>

<body class="bg-slate-50 dark:bg-neutral-950 text-slate-800 dark:text-neutral-100 font-sans min-h-screen flex flex-col">

<header class="bg-white dark:bg-neutral-950/80 border-b border-gray-200 dark:border-neutral-800 md:sticky md:top-0 z-50 shadow-sm backdrop-blur">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">

      <div class="flex items-center gap-3">
        <a href="<?= h(base_path('view/index.php')) ?>" class="flex items-center gap-3 no-underline hover:no-underline text-inherit">
          <?= getLogoHtml($uiCfg) ?>
          <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-neutral-50 leading-tight hover:text-gray-700 dark:hover:text-neutral-200 transition-colors"><?= h($uiCfg['title'] ?? 'Förmågekarta') ?></h1>
            <p class="text-xs text-gray-500 dark:text-neutral-400 uppercase tracking-wider">
              <?= h($uiCfg['subtitle'] ?? 'Vy: Strategisk mognad') ?> • <?= h($uiCfg['heat_label'] ?? 'heat') ?>: <?= h($heatField) ?>
            </p>
          </div>
        </a>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto items-center">
        <div class="relative w-full sm:w-72">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <input type="text" id="searchInput" placeholder="<?= h($uiCfg['search_placeholder'] ?? 'Sök förmåga...') ?>"
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-neutral-700 rounded-md leading-5 bg-gray-50 dark:bg-neutral-900 text-sm placeholder-gray-500 dark:placeholder-slate-500 focus:outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-inera-blue focus:ring-1 focus:ring-inera-blue transition duration-150 ease-in-out">
        </div>

        <?php if (count($contentDirs) > 1): ?>
        <div class="relative">
          <select id="contentDirSelect"
                  class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-neutral-700 rounded-md leading-5 bg-gray-50 dark:bg-neutral-900 text-sm text-gray-700 dark:text-neutral-200 focus:outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-inera-blue focus:ring-1 focus:ring-inera-blue transition duration-150 ease-in-out">
            <?php foreach ($contentDirs as $key => $dir): ?>
              <option value="<?= h($key) ?>" <?= $key === $selectedKey ? 'selected' : '' ?>>
                📁 <?= h($dir['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="relative">
          <div class="flex items-center gap-2">
            <button type="button" data-filter-toggle class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
              <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z"/>
              </svg>
              <?= h($uiCfg['filter_button_text'] ?? 'Filter') ?>
            </button>

            <a href="<?= h(base_path('view/help.php')) ?>"
               class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
               title="Hjälp & Best Practices">
              <svg class="h-5 w-5 text-gray-600 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </a>
            <button class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition"
                    type="button" data-theme-toggle aria-label="Växla tema">
              🌓
            </button>
          </div>

          <div id="filterPanel" class="hidden absolute right-0 mt-2 w-[420px] max-w-[calc(100vw-2rem)] bg-white dark:bg-neutral-950 border border-gray-200 dark:border-neutral-800 rounded-xl shadow-lg p-3">
            <div class="text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider mb-2">Mognadsfilter</div>

            <div class="flex flex-wrap sm:flex-nowrap gap-2">
              <button type="button" onclick="setFilter('all')" id="btn-all" class="filter-btn active px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent">
                Alla
              </button>
              <button type="button" onclick="setFilter('1')" id="btn-1" class="filter-btn px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-red-500"></span> 1
              </button>
              <button type="button" onclick="setFilter('2')" id="btn-2" class="filter-btn px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span> 2
              </button>
              <button type="button" onclick="setFilter('3')" id="btn-3" class="filter-btn px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-yellow-400"></span> 3
              </button>
              <button type="button" onclick="setFilter('4')" id="btn-4" class="filter-btn px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-lime-500"></span> 4
              </button>
              <button type="button" onclick="setFilter('5')" id="btn-5" class="filter-btn px-3 py-1.5 rounded-md text-xs font-medium text-gray-600 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-900 transition border border-transparent flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-green-500"></span> 5
              </button>
            </div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
  <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-neutral-300 select-none">
    <input id="toggleDesc" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-neutral-700" checked>
    Visa beskrivning
  </label>

  <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-neutral-300 select-none">
    <input id="toggleId" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-neutral-700">
    Visa ID
  </label>

  <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-neutral-300 select-none">
    <input id="toggleMeta" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-neutral-700" checked>
    Visa metadata (owner/status/mognad)
  </label>

  <label class="flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-neutral-300 select-none">
    <input id="toggleTags" type="checkbox" class="h-4 w-4 rounded border-gray-300 dark:border-neutral-700" checked>
    Visa taggar
  </label>
</div>
<div class="mt-3 text-xs text-gray-500 dark:text-neutral-400">
              Tips: Sök + filter dimmar övriga kort.
            </div>

            <div class="mt-3 flex items-center justify-between">
              <a href="<?= h(base_path('editor/index.php')) ?>" class="text-xs font-semibold text-inera-blue hover:underline">Gå till Editor</a>
              <button type="button" class="text-xs px-2 py-1 rounded-md border border-gray-200 dark:border-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-900"
                      onclick="document.getElementById('searchInput').value=''; searchQuery=''; setFilter('all'); updateUI();">
                Rensa
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</header>

<main id="captureRoot" class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">

<?php foreach (($tax['layers'] ?? []) as $layerKey => $layerLabel): 
  [$title, $chrome] = sectionChrome($layerKey, $tax);
  $areas = $groups[$layerKey] ?? [];
  $areaCount = count($areas);
  
  // Get layout configuration
  $layout = $viewCfg['layout'] ?? [];
  $maxColsSm = min($layout['max_columns_sm'] ?? 2, $areaCount);
  $maxColsMd = min($layout['max_columns_md'] ?? 3, $areaCount);
  $maxColsLg = min($layout['max_columns_lg'] ?? 4, $areaCount);
  
  // Calculate optimal grid classes based on area count and configuration
  $gridClasses = "grid-cols-1 sm:grid-cols-{$maxColsSm} md:grid-cols-{$maxColsMd} lg:grid-cols-{$maxColsLg}";
?>
<section>
  <div class="flex items-center gap-2 mb-3">
    <span class="h-px flex-1 <?= $layerKey==='karnprocesser' ? 'bg-inera-blue opacity-30' : 'bg-gray-300 dark:bg-slate-800' ?>"></span>
    <h2 class="text-xs font-bold <?= $layerKey==='karnprocesser' ? 'text-inera-blue' : 'text-gray-400 dark:text-neutral-400' ?> uppercase tracking-[0.2em]">
      <?= h($title) ?>
    </h2>
    <span class="h-px flex-1 <?= $layerKey==='karnprocesser' ? 'bg-inera-blue opacity-30' : 'bg-gray-300 dark:bg-slate-800' ?>"></span>
  </div>

  <div class="border rounded-xl p-4 <?= h($chrome) ?>">
    <?php if (!$areas): ?>
      <div class="text-sm text-gray-500 dark:text-neutral-400">Inga förmågor ännu i detta skikt.</div>
    <?php else: ?>
      <div class="grid <?= h($gridClasses) ?> gap-4">
        <?php foreach ($areas as $areaName => $list): ?>
          <div class="domain-group">
            <h3 class="font-bold text-sm mb-3 border-b pb-1
              <?= $layerKey==='karnprocesser' ? 'text-inera-dark dark:text-neutral-200 border-blue-100 dark:border-neutral-800' : 'text-gray-600 dark:text-neutral-200 border-gray-200 dark:border-neutral-800' ?>">
              <?= h($areaName) ?>
            </h3>

            <div class="cap-list flex flex-wrap gap-2">
              <?php foreach ($list as $cap): 
                $m = (int)($cap->get($heatField, 0));
                $m = max(0, min(5, $m));
                $border = maturityColorClass($m ?: 1);
              ?>
<a href="<?= h(base_path('view/capability.php?id=' . rawurlencode($cap->id))) ?>"
   class="capability-card cap-card block bg-white/95 dark:bg-neutral-900/80 p-2.5 rounded-md border border-gray-200/70 dark:border-neutral-700/70 border-l-[5px] <?= h($border) ?> shadow-sm hover:shadow-md"
   data-maturity="<?= h((string)$m) ?>">
  <div class="flex justify-between items-start gap-3">
    <h4 class="font-semibold text-sm text-gray-900 dark:text-neutral-50 leading-snug pr-2">
      <?= h($cap->name) ?>
    </h4>
    <span class="cap-id shrink-0 text-[10px] font-mono text-gray-400 dark:text-neutral-500">
      <?= h($cap->id) ?>
    </span>
  </div>

  <?php if ($cap->description): ?>
    <p class="cap-desc text-[11px] text-gray-500 dark:text-neutral-400 mt-1 line-clamp-2">
      <?= h($cap->description) ?>
    </p>
  <?php endif; ?>

  <?php
    $metaBits = [];
    if ($cap->get('owner'))    $metaBits[] = $cap->get('owner');
    if ($cap->get('status'))   $metaBits[] = $cap->get('status');
    if ($cap->get('maturity')) $metaBits[] = 'M' . $cap->get('maturity');
  ?>
  <?php if (!empty($metaBits)): ?>
    <div class="cap-meta mt-1 flex flex-wrap gap-1.5">
      <?php foreach ($metaBits as $b): ?>
        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-neutral-300 border border-slate-200 dark:border-neutral-700">
          <?= h($b) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php $tags = $cap->get('tags'); if (is_array($tags)) $tags = array_filter($tags); ?>
  <?php if (!empty($tags)): ?>
    <div class="cap-tags mt-1 flex flex-wrap gap-1.5">
      <?php foreach ((array)$tags as $t): ?>
        <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-200 border border-blue-200 dark:border-blue-500/30">
          <?= h($t) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</a>
<?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endforeach; ?>

</main>

<footer class="bg-white dark:bg-neutral-950 border-t border-gray-200 dark:border-neutral-800 mt-auto py-5">
  <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
    <div class="text-xs text-gray-500 dark:text-neutral-400">
      <span class="font-semibold">Förmågekarta</span> • Export & utskrift
    </div>

    <div class="flex items-center gap-2 no-print">
      <button id="btnPng" type="button"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
        <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/>
        </svg>
        Spara som PNG
      </button>

      <div class="relative">
        <button id="btnExportDropdown" 
          class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
          <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v16H4V4zm4 4h8M8 8v8m4-8v8"/>
          </svg>
          <?= h($uiCfg['export_excel_text'] ?? 'Exportera till Excel') ?>
          <svg class="h-3 w-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
        </button>
        <div id="exportDropdown" class="absolute right-0 bottom-full mb-2 w-56 bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-md shadow-lg z-50 hidden">
          <div class="py-1">
            <a href="<?= h(base_path('view/export_excel.php?scope=current')) ?>" 
               class="block px-4 py-2 text-sm text-gray-700 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700">
              <div class="font-medium"><?= h($uiCfg['export_options']['current']['title'] ?? 'Aktuell katalog') ?></div>
              <div class="text-xs text-gray-500 dark:text-neutral-400"><?= h($uiCfg['export_options']['current']['description'] ?? 'Endast denna vy') ?></div>
            </a>
            <a href="<?= h(base_path('view/export_excel.php?scope=all')) ?>" 
               class="block px-4 py-2 text-sm text-gray-700 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-neutral-700">
              <div class="font-medium"><?= h($uiCfg['export_options']['all']['title'] ?? 'Alla kataloger') ?></div>
              <div class="text-xs text-gray-500 dark:text-neutral-400"><?= h($uiCfg['export_options']['all']['description'] ?? 'Alla tillgängliga data') ?></div>
            </a>
          </div>
        </div>
      </div>

      <button id="btnPrint" type="button"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
        <svg class="h-4 w-4 text-gray-500 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>
        </svg>
        <?= h($uiCfg['print_button_text'] ?? 'Print') ?>
      </button>

      <a href="<?= h(base_path('editor/index.php')) ?>"
        class="inline-flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 hover:bg-gray-50 dark:hover:bg-neutral-800 transition">
        Editor
      </a>
    </div>
  </div>
</footer>


<script>
  // --- Filter + search state ---
  let activeFilter = 'all';
  let searchQuery = '';

  const cards = document.querySelectorAll('.capability-card');
  const searchInput = document.getElementById('searchInput');
  const buttons = document.querySelectorAll('.filter-btn');

  function setFilter(level){ activeFilter = level; updateUI(); }

  function updateUI(){
    // Buttons
    buttons.forEach(btn => {
      if (btn.id === `btn-${activeFilter}`) btn.classList.add('active', 'ring-2', 'ring-offset-1', 'ring-blue-300');
      else btn.classList.remove('active', 'ring-2', 'ring-offset-1', 'ring-blue-300');
    });

    // Cards
    cards.forEach(card => {
      const maturity = card.getAttribute('data-maturity') || '';
      const textContent = (card.innerText || '').toLowerCase();

      const matchesMaturity = (activeFilter === 'all') || (maturity === activeFilter);
      const matchesSearch = (searchQuery === '') || (textContent.includes(searchQuery.toLowerCase()));

      if (matchesMaturity && matchesSearch) {
        card.classList.remove('is-dimmed');
        if (searchQuery !== '') card.classList.add('is-highlighted');
        else card.classList.remove('is-highlighted');
      } else {
        card.classList.add('is-dimmed');
        card.classList.remove('is-highlighted');
      }
    });
  }

  if(searchInput){
    searchInput.addEventListener('input', (e) => {
      searchQuery = (e.target.value || '').trim();
      updateUI();
    });
  }

  // --- Card toggles (persisted) ---
  const descKey = 'capmap_show_desc';
  const idKey   = 'capmap_show_id';
  const metaKey = 'capmap_show_meta';
  const tagsKey = 'capmap_show_tags';

  const toggleDesc = document.getElementById('toggleDesc');
  const toggleId   = document.getElementById('toggleId');
  const toggleMeta = document.getElementById('toggleMeta');
  const toggleTags = document.getElementById('toggleTags');

  function loadBool(key, fallback){
    try{
      const v = localStorage.getItem(key);
      if(v === null) return fallback;
      return v === '1';
    }catch(e){ return fallback; }
  }
  function saveBool(key, val){
    try{ localStorage.setItem(key, val ? '1' : '0'); }catch(e){}
  }

  let showDesc = loadBool(descKey, true);
  let showId   = loadBool(idKey,   false); // hidden by default
  let showMeta = loadBool(metaKey, true);
  let showTags = loadBool(tagsKey, true);

  function applyCardToggles(){
    const root = document.documentElement;
    root.classList.toggle('hide-desc', !showDesc);
    root.classList.toggle('hide-id',   !showId);
    root.classList.toggle('hide-meta', !showMeta);
    root.classList.toggle('hide-tags', !showTags);

    if(toggleDesc) toggleDesc.checked = showDesc;
    if(toggleId)   toggleId.checked   = showId;
    if(toggleMeta) toggleMeta.checked = showMeta;
    if(toggleTags) toggleTags.checked = showTags;
  }

  function bindToggle(el, key, setter){
    if(!el) return;
    el.addEventListener('change', ()=>{
      setter(!!el.checked);
      saveBool(key, el.checked);
      applyCardToggles();
    });
  }

  bindToggle(toggleDesc, descKey, (v)=> showDesc = v);
  bindToggle(toggleId,   idKey,   (v)=> showId   = v);
  bindToggle(toggleMeta, metaKey, (v)=> showMeta = v);
  bindToggle(toggleTags, tagsKey, (v)=> showTags = v);

  // Init
  applyCardToggles();
  updateUI();

  // Content directory switcher
  const csrfToken = '<?= h(csrf_token()) ?>';
  const contentDirSelect = document.getElementById('contentDirSelect');
  if(contentDirSelect){
    contentDirSelect.addEventListener('change', async (e) => {
      const key = e.target.value;
      const originalValue = e.target.value;

      try {
        const formData = new FormData();
        formData.append('key', key);
        formData.append('csrf_token', csrfToken);

        const response = await fetch('<?= h(base_path('view/switch_content.php')) ?>', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if(result.success){
          // Reload page to show new content
          window.location.reload();
        } else {
          alert('Kunde inte byta katalog: ' + (result.error || 'Okänt fel'));
          e.target.value = originalValue;
        }
      } catch(error) {
        console.error('Error switching content directory:', error);
        alert('Ett fel uppstod vid byte av katalog');
        e.target.value = originalValue;
      }
    });
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
  // PNG export: capture visible map
  const btnPng = document.getElementById('btnPng');
  const btnPrint = document.getElementById('btnPrint');

  if(btnPrint){
    btnPrint.addEventListener('click', ()=> window.print());
  }

  if(btnPng){
    btnPng.addEventListener('click', async ()=>{
      const root = document.getElementById('captureRoot');
      if(!root || !window.html2canvas) return;

      // Show loading state
      const originalText = btnPng.innerHTML;
      btnPng.disabled = true;
      btnPng.style.opacity = '0.6';
      btnPng.style.cursor = 'wait';
      btnPng.innerHTML = '<svg class="h-4 w-4 inline-block" style="animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Exporterar...';

      // Add keyframe animation inline
      if(!document.getElementById('spin-animation')){
        const style = document.createElement('style');
        style.id = 'spin-animation';
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
      }

      try {
        // Temporarily close filter panel for a clean capture
        const fp = document.getElementById('filterPanel');
        const wasOpen = fp && !fp.classList.contains('hidden');
        if(fp) fp.classList.add('hidden');

        // Remove dimmed cards temporarily for export
        const dimmedCards = document.querySelectorAll('.is-dimmed');
        dimmedCards.forEach(card => card.classList.add('temp-export-show'));

        // Wait for fonts to load
        if(document.fonts && document.fonts.ready){
          await document.fonts.ready;
        }

        // Small delay to ensure rendering is complete
        await new Promise(resolve => setTimeout(resolve, 100));

        // Calculate optimal scale for quality while keeping file size reasonable
        const scale = 2;

        // Get actual background color (handle dark mode)
        const bgColor = getComputedStyle(document.body).backgroundColor;

        const canvas = await html2canvas(root, {
          backgroundColor: bgColor,
          scale: scale,
          useCORS: true,
          allowTaint: true,
          logging: false,
          width: root.scrollWidth,
          height: root.scrollHeight,
          windowWidth: root.scrollWidth,
          windowHeight: root.scrollHeight,
          x: 0,
          y: 0,
          scrollX: 0,
          scrollY: 0,
          imageTimeout: 15000,
          onclone: function(clonedDoc) {
            // Ensure all text is visible in the clone
            const clonedRoot = clonedDoc.getElementById('captureRoot');
            if(clonedRoot){
              clonedRoot.style.transform = 'none';
              clonedRoot.style.maxWidth = 'none';
              clonedRoot.style.width = 'auto';

              // Remove any hidden/dimmed elements
              const hidden = clonedRoot.querySelectorAll('.is-dimmed');
              hidden.forEach(el => {
                el.style.opacity = '1';
                el.style.filter = 'none';
                el.style.transform = 'none';
                el.style.pointerEvents = 'auto';
              });

              // Ensure all text is visible and properly rendered
              const allText = clonedRoot.querySelectorAll('*');
              allText.forEach(el => {
                el.style.textOverflow = 'clip';
                el.style.overflow = 'visible';
                // Remove line clamps for export
                if(el.classList.contains('line-clamp-2')){
                  el.style.webkitLineClamp = 'unset';
                  el.style.display = 'block';
                }
              });

              // Ensure cards are fully visible
              const cards = clonedRoot.querySelectorAll('.capability-card');
              cards.forEach(card => {
                card.style.pageBreakInside = 'avoid';
                card.style.breakInside = 'avoid';
              });
            }
          }
        });

        // Restore UI state
        if(wasOpen && fp) fp.classList.remove('hidden');
        dimmedCards.forEach(card => card.classList.remove('temp-export-show'));

        // Download the image
        const a = document.createElement('a');
        const ts = new Date().toISOString().slice(0,10);
        a.download = `formagekarta_${ts}.png`;
        a.href = canvas.toDataURL('image/png', 0.95);
        document.body.appendChild(a);
        a.click();
        a.remove();

      } catch(error) {
        console.error('Export failed:', error);
        alert('Exporten misslyckades. Försök igen.');
      } finally {
        // Restore button state
        btnPng.disabled = false;
        btnPng.style.opacity = '';
        btnPng.style.cursor = '';
        btnPng.innerHTML = originalText;
      }
    });
  }
</script>

</body>

</html>
