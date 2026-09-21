<?php
require __DIR__ . '/../editor/_auth.php';
$mapInterface = 'new';
require __DIR__ . '/../app/map_interface.php';
header('Content-Type: text/html; charset=UTF-8');

$tax = cfg('taxonomy');
$dirs = readable_content_dirs();
$selectedKey = get_selected_content_key();
if (!isset($dirs[$selectedKey])) {
  $fallback = array_key_first($dirs);
  if ($fallback !== null) { set_content_dir($fallback); $selectedKey = $fallback; }
}
require_read($selectedKey);
// Only offer maps that actually contain capabilities, so empty/test
// directories (e.g. "Alternativ katalog") don't clutter the map picker.
// The currently selected map always stays listed, even if it's empty.
$pickerDirs = array_filter($dirs, function ($dirInfo, $key) use ($selectedKey) {
  return $key === $selectedKey || (new App\CapabilityRepository((string)$dirInfo['path']))->all();
}, ARRAY_FILTER_USE_BOTH);
// Keep readable parents visible even when they contain no capabilities.
foreach (cfg('app')['map_parents'] ?? [] as $child => $parent) {
  if (isset($pickerDirs[$child], $dirs[$parent])) $pickerDirs[$parent] = $dirs[$parent];
}
$pickerDirs = array_intersect_key($dirs, $pickerDirs);
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
$layers = array_unique(array_merge(['ledning_styrning', 'karnprocesser', 'verksamhetsstod'], array_keys($tax['layers'] ?? []), array_keys($groups)));
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
  <a class="brand" href="<?= h(base_path('view/overview.php?map=' . rawurlencode($selectedKey))) ?>" style="text-decoration:none;color:inherit" title="Tillbaka till hela förmågekartan"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong><?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?></strong><span>Verksamhetens förmågor, samlade</span></div></a>
  <nav aria-label="Vyer och verktyg">
    <?php if (App\Auth::isAdministrator()): ?><a href="<?= h(base_path('admin/')) ?>">Admin</a><?php endif; ?>
    <a href="<?= h(base_path('view/tags.php')) ?>">Taggar</a>
    <?php require __DIR__ . '/../app/templates/interface_toggle.php'; ?>
    <form action="<?= h(base_path('view/overview.php')) ?>" method="get" class="map-picker header-map-picker"><label for="map-select">Välj karta</label><div><select id="map-select" name="map"><?php foreach (map_picker_dirs($pickerDirs) as $key => $dir): ?><option value="<?= h($key) ?>" <?= $key === $selectedKey ? 'selected' : '' ?>><?= h($dir['label'] ?? $key) ?></option><?php endforeach; ?></select><button type="submit">Visa</button></div></form>
    <a href="<?= h(base_path('editor/index.php' . $mapQuery)) ?>">Editor</a>
    <div class="export-menu">
      <button type="button" id="export-menu-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="export-menu-content">Exportera <span class="chevron-icon" aria-hidden="true"></span></button>
      <div class="export-menu-content" id="export-menu-content" hidden>
        <p>Denna karta</p>
        <a href="<?= h(base_path('view/export_csv.php?scope=current&map=' . rawurlencode($selectedKey))) ?>">CSV</a>
        <a href="<?= h(base_path('view/export_excel.php?scope=current&map=' . rawurlencode($selectedKey))) ?>">Excel</a>
        <a href="<?= h(base_path('view/export_archimate.php?scope=current&map=' . rawurlencode($selectedKey))) ?>">ArchiMate 3.1 (XML)</a>
        <p>Alla kartor</p>
        <a href="<?= h(base_path('view/export_csv.php?scope=all&map=' . rawurlencode($selectedKey))) ?>">CSV</a>
        <a href="<?= h(base_path('view/export_excel.php?scope=all&map=' . rawurlencode($selectedKey))) ?>">Excel</a>
        <a href="<?= h(base_path('view/export_archimate.php?scope=all&map=' . rawurlencode($selectedKey))) ?>">ArchiMate 3.1 (XML)</a>
      </div>
    </div>
    <?php if (current_user() !== null): ?>
      <span class="header-user" title="Inloggad som <?= h(user_display_name()) ?>"><?= h(user_display_name()) ?></span>
      <a href="<?= h(base_path('editor/logout.php')) ?>">Logga ut</a>
    <?php elseif (!App\Auth::isOpen()): ?>
      <a href="<?= h(base_path('editor/login.php')) ?>">Logga in</a>
    <?php endif; ?>
    <button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema" title="Växla tema">◐</button>
  </nav>
</header>
<main>
  <details class="overview-controls">
    <summary><span><?= h($dirs[$selectedKey]['label'] ?? 'Förmågekarta') ?></span><span class="controls-label">Filter och statistik <span class="chevron-icon" aria-hidden="true"></span></span></summary>
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
    <fieldset class="card-display-options">
      <legend>Visa på korten</legend>
      <?php foreach (['description' => 'Beskrivning', 'id' => 'ID', 'tags' => 'Taggar', 'metadata' => 'Metadata', 'area' => 'Område och typ', 'maturity' => 'Mognadsetikett'] as $field => $label): ?>
        <label><input type="checkbox" data-card-option="<?= h($field) ?>" <?= in_array($field, ['description', 'maturity'], true) ? 'checked' : '' ?>> <?= h($label) ?></label>
      <?php endforeach; ?>
      <p>Metadata visar ansvarig, status, nivå, kritikalitet, risk och uppdateringsdatum när uppgifterna finns. Valen sparas för den nya vyn i din webbläsare.</p>
    </fieldset>
  </section>
    </div>
  </details>
  <div class="map-caption"><p id="result-count" role="status" aria-live="polite"><?= count($caps) ?> förmågor</p><span>Kortens färg visar mognad</span></div>
  <div id="map">
  <?php foreach ($layers as $index => $layer): $groups[$layer] = $groups[$layer] ?? []; ksort($groups[$layer]); ?>
    <section class="layer" data-section="<?= h($layer) ?>">
      <header class="layer-header"><h2><button type="button" class="layer-badge" data-layer-heading="<?= h($layer) ?>" aria-expanded="true" title="Visa eller dölj förmågorna i detta skikt"><?= h($layerChrome[$layer][0] ?? $tax['layer_display_names'][$layer] ?? $tax['layers'][$layer] ?? $layer) ?> <span class="layer-toggle-icon chevron-icon" aria-hidden="true"></span></button></h2><span class="layer-count"><?= array_sum(array_map('count', $groups[$layer])) ?> förmågor</span><span class="layer-line"></span><span class="layer-description"><?= h($layerChrome[$layer][1] ?? '') ?></span></header>
      <div class="layer-cards">
      <?php foreach ($groups[$layer] as $area => $list): ?>
      <section class="area"><h3><?= h($area) ?></h3><div class="cards-grid">
        <?php foreach ($list as $cap): $m = (int)$cap->get('maturity', 0); if ($m < 1 || $m > 5) $m = 0;
          $tags = $cap->get('tags', []); if (!is_array($tags)) $tags = [$tags];
          $search = $cap->name . ' ' . $cap->description . ' ' . $cap->id . ' ' . $area . ' ' . json_encode($tags, JSON_UNESCAPED_UNICODE);
        ?>
        <article class="cap-card maturity-<?= $m ?>" data-capability data-search="<?= h($search) ?>" data-layer="<?= h($layer) ?>" data-area="<?= h($area) ?>" data-maturity="<?= $m ?>">
        <a style="display:flex;flex-direction:column;flex:1;color:inherit;text-decoration:none" href="<?= h(base_path('view/capability_new.php?id=' . rawurlencode($cap->id) . '&map=' . rawurlencode($selectedKey))) ?>">
          <span class="overview-card-id" data-card-field="id" hidden><?= h($cap->id) ?></span>
          <?php if (isset($cap->meta['redirect_map']) && is_authed()): ?><span class="overview-card-context" title="Visar originalförmågan från en annan plats">↗ Länkad förmåga</span><?php endif; ?>
          <h4><?= h($cap->name) ?></h4><p class="card-desc" data-card-field="description"><?= h($cap->description ?: 'Ingen beskrivning angiven.') ?></p>
          <div class="overview-card-context" data-card-field="area" hidden><?= h($area) ?><?php if ($cap->type): ?> · <?= h($tax['types'][$cap->type] ?? $cap->type) ?><?php endif; ?></div>
          <?php $metadata = [];
            foreach (['owner' => 'Ansvarig', 'status' => 'Status', 'level' => 'Nivå', 'criticality' => 'Kritikalitet', 'risk_level' => 'Risk', 'updated' => 'Uppdaterad'] as $key => $label) {
              $value = $cap->get($key);
              if (is_scalar($value) && trim((string)$value) !== '' && $value !== false) $metadata[$label] = (string)$value;
            }
          ?>
          <?php if ($metadata): ?><dl class="overview-card-meta" data-card-field="metadata" hidden>
            <?php foreach ($metadata as $label => $value): ?><div><dt><?= h($label) ?></dt><dd><?= h($value) ?></dd></div><?php endforeach; ?>
          </dl><?php endif; ?>
          <?php $visibleTags = array_filter($tags, fn($tag) => is_scalar($tag) && trim((string)$tag) !== ''); ?>
          <?php if ($visibleTags): ?><div class="overview-card-tags" data-card-field="tags" aria-label="Taggar" hidden>
            <?php foreach ($visibleTags as $tag): ?><span><?= h((string)$tag) ?></span><?php endforeach; ?>
          </div><?php endif; ?>
          <div class="card-bottom" data-card-field="maturity"><span class="status-badge"><?= h($labels[$m]) ?></span></div>
        </a>
        <?php require __DIR__ . '/../app/templates/reference_edit_link.php'; ?>
        </article>
        <?php endforeach; ?>
      </div></section>
      <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  </div>
  <p id="empty-state" class="empty-state" <?= $caps ? 'hidden' : '' ?>>Inga förmågor att visa. Prova en annan karta eller ändra filtren.</p>
  <footer><strong>Mognadsnivåer</strong><div class="legend"><?php foreach ($labels as $m => $label): ?><button type="button" class="maturity-<?= $m ?>" data-maturity-legend="<?= $m ?>" aria-pressed="false" title="Färgmarkera förmågor med denna mognadsnivå"><i aria-hidden="true"></i><?= $m ? $m . ' ' : '' ?><?= h($label) ?></button><?php endforeach; ?></div><p>Klicka på ett skikt för att visa endast matchande förmågor, eller på en mognadsnivå för att färgmarkera matchande förmågor.</p></footer>
</main>
</body>
</html>
