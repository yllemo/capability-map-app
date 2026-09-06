<?php
require __DIR__ . '/../app/bootstrap.php';
$mapInterface = 'new';
require __DIR__ . '/../app/map_interface.php';
header('Content-Type: text/html; charset=UTF-8');

$tax = cfg('taxonomy');
$dirs = get_content_dirs();
$selectedKey = get_selected_content_key();
$caps = (new App\CapabilityRepository(get_content_dir()))->all();
$groups = [];
$areas = [];
$knownMaturity = [];
foreach ($caps as $cap) {
  $groups[$cap->layer ?: 'unknown'][$cap->area ?: 'Övrigt'][] = $cap;
  $areas[$cap->area ?: 'Övrigt'] = true;
  $m = (int)$cap->get('maturity', 0);
  if ($m >= 1 && $m <= 5) $knownMaturity[] = $m;
}
ksort($areas);
$layers = array_unique(array_merge(array_keys($tax['layers'] ?? []), array_keys($groups)));
$labels = [0 => 'Ej bedömd', 1 => 'Initial', 2 => 'Under utveckling', 3 => 'Definierad', 4 => 'Hanterad', 5 => 'Optimerad'];
$mapQuery = '?map=' . rawurlencode($selectedKey);
$layerChrome = [
  'ledning_styrning' => ['▲ Strategiskt skikt (Direction)', 'Styrande förmågor'],
  'karnprocesser' => ['● Värdeskapande skikt (Core)', 'Kärnverksamhet'],
  'verksamhetsstod' => ['▼ Stödjande skikt (Enabling)', 'Möjliggörande förmågor'],
];
?><!doctype html>
<html lang="sv">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Förmågekarta · Översikt</title>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/interface-toggle.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
  <script defer src="<?= h(base_path('assets/overview.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#map">Hoppa till förmågekartan</a>
<header class="site-header">
  <div class="brand"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong>Förmågekarta</strong><span>Verksamhetens förmågor, samlade</span></div></div>
  <nav aria-label="Vyer och verktyg">
    <?php require __DIR__ . '/../app/templates/interface_toggle.php'; ?>
    <form action="<?= h(base_path('view/overview.php')) ?>" method="get" class="map-picker header-map-picker"><label for="map-select">Välj karta</label><div><select id="map-select" name="map"><?php foreach ($dirs as $key => $dir): ?><option value="<?= h($key) ?>" <?= $key === $selectedKey ? 'selected' : '' ?>><?= h($dir['label'] ?? $key) ?></option><?php endforeach; ?></select><button type="submit">Visa</button></div></form>
    <a href="<?= h(base_path('editor/index.php' . $mapQuery)) ?>">Editor</a>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main>
  <details class="overview-controls">
    <summary><span><?= h($dirs[$selectedKey]['label'] ?? 'Förmågekarta') ?></span><span class="controls-label">Filter och statistik <span aria-hidden="true">⌄</span></span></summary>
    <div class="controls-content">
  <section class="intro" aria-labelledby="page-title">
    <div><p class="eyebrow">ÖVERSIKTSVY <span>NY VY</span></p><h1 id="page-title"><?= h($dirs[$selectedKey]['label'] ?? 'Förmågekarta') ?></h1><p>Från styrning till genomförande. Utforska förmågor och deras mognad.</p></div>
  </section>
  <section class="stats" aria-label="Sammanfattning för hela kartan">
    <div><strong><?= count($caps) ?></strong><span>Förmågor totalt</span></div>
    <div><strong><?= count($groups) ?></strong><span>Skikt</span></div>
    <div><strong><?= count($areas) ?></strong><span>Områden</span></div>
    <div><strong><?= $knownMaturity ? number_format(array_sum($knownMaturity) / count($knownMaturity), 1, ',', '') : '—' ?><small> / 5</small></strong><span>Mognad i snitt · <?= count($knownMaturity) ?> bedömda</span></div>
  </section>
  <section class="toolbar" aria-label="Filtrera förmågor">
    <div class="filter-row"><label class="search-label" for="overview-search">Sök förmåga<input id="overview-search" type="search" placeholder="Namn, beskrivning, ID eller tagg…"></label><label for="area-filter">Område<select id="area-filter"><option value="">Alla områden</option><?php foreach ($areas as $area => $_): ?><option><?= h($area) ?></option><?php endforeach; ?></select></label><label for="maturity-filter">Mognad<select id="maturity-filter"><option value="">Alla nivåer</option><?php foreach ($labels as $m => $label): ?><option value="<?= $m ?>"><?= $m ? $m . ' · ' : '' ?><?= h($label) ?></option><?php endforeach; ?></select></label></div>
    <div class="filter-bottom"><div class="chips" aria-label="Skikt"><button type="button" data-layer="" aria-pressed="true">Alla skikt</button><?php foreach ($layers as $layer): ?><button type="button" data-layer="<?= h($layer) ?>" aria-pressed="false"><?= h($tax['layer_display_names'][$layer] ?? $tax['layers'][$layer] ?? $layer) ?></button><?php endforeach; ?></div><button class="reset" type="button" id="reset-filters">Rensa filter</button></div>
  </section>
    </div>
  </details>
  <div class="map-caption"><p id="result-count" role="status" aria-live="polite"><?= count($caps) ?> förmågor</p><span>Kortens färg visar mognad</span></div>
  <div id="map">
  <?php foreach ($layers as $index => $layer): if (empty($groups[$layer])) continue; ksort($groups[$layer]); ?>
    <section class="layer" data-section="<?= h($layer) ?>">
      <header class="layer-header"><h2 class="layer-badge"><?= h($layerChrome[$layer][0] ?? $tax['layer_display_names'][$layer] ?? $tax['layers'][$layer] ?? $layer) ?></h2><span class="layer-count"><?= array_sum(array_map('count', $groups[$layer])) ?> förmågor</span><span class="layer-line"></span><span class="layer-description"><?= h($layerChrome[$layer][1] ?? '') ?></span></header>
      <div class="layer-cards">
      <?php foreach ($groups[$layer] as $area => $list): ?>
      <section class="area"><h3><?= h($area) ?></h3><div class="cards-grid">
        <?php foreach ($list as $cap): $m = (int)$cap->get('maturity', 0); if ($m < 1 || $m > 5) $m = 0;
          $tags = $cap->get('tags', []); if (!is_array($tags)) $tags = [$tags];
          $search = $cap->name . ' ' . $cap->description . ' ' . $cap->id . ' ' . $area . ' ' . json_encode($tags, JSON_UNESCAPED_UNICODE);
        ?>
        <a class="cap-card maturity-<?= $m ?>" data-capability data-search="<?= h($search) ?>" data-layer="<?= h($layer) ?>" data-area="<?= h($area) ?>" data-maturity="<?= $m ?>" href="<?= h(base_path('view/capability.php?id=' . rawurlencode($cap->id) . '&map=' . rawurlencode($selectedKey))) ?>">
          <h4><?= h($cap->name) ?></h4><p class="card-desc"><?= h($cap->description ?: 'Ingen beskrivning angiven.') ?></p>
          <div class="card-bottom"><span class="status-badge"><?= h($labels[$m]) ?></span></div>
        </a>
        <?php endforeach; ?>
      </div></section>
      <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  </div>
  <p id="empty-state" class="empty-state" <?= $caps ? 'hidden' : '' ?>>Inga förmågor att visa. Prova en annan karta eller ändra filtren.</p>
  <footer><strong>Mognadsnivåer</strong><div class="legend"><?php foreach ($labels as $m => $label): ?><span class="maturity-<?= $m ?>"><i aria-hidden="true"></i><?= $m ? $m . ' ' : '' ?><?= h($label) ?></span><?php endforeach; ?></div><p>Översiktsvyn använder samma innehåll som den klassiska kartan.</p></footer>
</main>
</body>
</html>
