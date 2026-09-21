<?php
require __DIR__ . '/../editor/_auth.php';
header('Content-Type: text/html; charset=UTF-8');
$param = static fn(string $key): string => is_string($_GET[$key] ?? '') ? trim($_GET[$key] ?? '') : '';
$tag = $param('tag');
$query = $param('q');
$map = $param('map');
$sort = in_array($param('sort'), ['alpha', 'map', 'count'], true) ? $param('sort') : 'alpha';
$limit = in_array($param('limit'), ['30', '60', '100'], true) ? (int)$param('limit') : 30;
$dirs = readable_content_dirs();
if ($map !== '' && !isset($dirs[$map])) access_denied('Du har inte behörighet att läsa den här förmågekartan.');
$fold = static fn(string $value): string => function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower(strtr($value, ['Å'=>'å', 'Ä'=>'ä', 'Ö'=>'ö']));
$pageUrl = static function (array $changes = []) use ($map, $tag, $query, $sort, $limit): string {
  $params = array_replace(['tag'=>$tag, 'map'=>$map, 'q'=>$query, 'sort'=>$sort, 'limit'=>$limit], $changes);
  return base_path('view/tags.php?' . http_build_query(array_filter($params, static fn($v) => $v !== ''), '', '&', PHP_QUERY_RFC3986));
};
$url = static fn(string $value): string => $pageUrl(['tag'=>$value, 'q'=>'']);
$tags = [];
$rows = [];
foreach ($dirs as $key => $dir) {
  if ($map !== '' && (string)$key !== $map) continue;
  foreach ((new App\CapabilityRepository((string)$dir['path']))->all() as $cap) {
    $capTags = [];
    foreach ((array)$cap->get('tags', []) as $value) {
      if (!is_string($value) && !is_numeric($value)) continue;
      $value = trim((string)$value);
      if ($value !== '') $capTags[$fold($value)] = $value;
    }
    foreach ($capTags as $normalized => $value) {
      $tagKey = $sort === 'map' ? json_encode([(string)$key, $normalized]) : $normalized;
      if (!isset($tags[$tagKey])) $tags[$tagKey] = ['name'=>$value, 'count'=>0, 'map'=>(string)$key, 'label'=>$dir['label'] ?? (string)$key];
      $tags[$tagKey]['count']++;
    }
    if (!$capTags || ($tag !== '' && !isset($capTags[$fold($tag)]))) continue;
    if ($query !== '' && !str_contains($fold(implode(' ', $capTags)), $fold($query))) continue;
    $rows[] = ['cap'=>$cap, 'map'=>(string)$key, 'label'=>$dir['label'] ?? (string)$key, 'tags'=>$capTags];
  }
}
$collator = class_exists('Collator') ? new Collator('sv_SE') : null;
$compare = static function(string $a, string $b) use ($collator, $fold): int {
  return $collator ? $collator->compare($a, $b) : strnatcmp($fold($a), $fold($b));
};
$tags = array_filter($tags, static fn($item) => $query === '' || str_contains($fold($item['name']), $fold($query)));
uasort($tags, static function($a, $b) use ($sort, $compare): int {
  if ($sort === 'count' && $a['count'] !== $b['count']) return $b['count'] <=> $a['count'];
  if ($sort === 'map') {
    $group = $compare($a['label'], $b['label']) ?: strcmp($a['map'], $b['map']);
    if ($group) return $group;
  }
  return $compare($a['name'], $b['name']) ?: strcmp($a['name'], $b['name']);
});
$tagCount = count($tags);
$pages = max(1, (int)ceil($tagCount / $limit));
$page = min($pages, max(1, (int)$param('page')));
$tags = array_slice($tags, ($page - 1) * $limit, $limit);
usort($rows, static fn($a, $b) => strnatcasecmp($a['cap']->name, $b['cap']->name));
$rowCount = count($rows);
$resultPages = max(1, (int)ceil($rowCount / 30));
$resultPage = min($resultPages, max(1, (int)$param('result_page')));
$rows = array_slice($rows, ($resultPage - 1) * 30, 30);
$tax = cfg('taxonomy');
$maturityLabels = [0=>'Ej bedömd', 1=>'Initial', 2=>'Under utveckling', 3=>'Definierad', 4=>'Hanterad', 5=>'Optimerad'];
?><!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Taggar · Förmågekarta</title>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/tags.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#results">Hoppa till förmågorna</a>
<header class="site-header">
  <a class="brand tags-brand" href="<?= h(base_path('view/overview.php')) ?>"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong>Förmågekarta</strong><span>Utforska via taggar</span></div></a>
  <nav aria-label="Vyer"><?php if (App\Auth::isAdministrator()): ?><a href="<?= h(base_path('admin/')) ?>">Admin</a><?php endif; ?><a href="<?= h(base_path('view/overview.php')) ?>">Förmågekarta</a><a href="<?= h(base_path('view/tags.php')) ?>" aria-current="page">Taggar</a><button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema">◐</button></nav>
</header>
<main class="tags-main">
  <section class="intro"><div><p class="eyebrow">UTFORSKA FÖRMÅGOR</p><h1><?= $tag !== '' ? 'Förmågor med taggen ' . h($tag) : 'Taggar' ?></h1><p><?= $tag !== '' ? 'Hitta förmågor med taggen ' . h($tag) . ', inom en karta eller över flera kartor.' : 'Hitta förmågor med gemensamma taggar, inom en karta eller över flera kartor.' ?></p></div></section>
  <form class="toolbar" method="get" action="<?= h(base_path('view/tags.php')) ?>">
    <div class="filter-row">
      <label class="search-label" for="tag-search">Sök taggar<input type="search" id="tag-search" name="q" value="<?= h($query) ?>" placeholder="Skriv hela eller delar av en tagg…"></label>
      <label for="tag-map">Karta<select id="tag-map" name="map"><option value="">Alla tillgängliga kartor</option><?php foreach (map_picker_dirs($dirs) as $key=>$dir): ?><option value="<?= h((string)$key) ?>" <?= (string)$key === $map ? 'selected' : '' ?>><?= h($dir['label'] ?? (string)$key) ?></option><?php endforeach; ?></select></label>
      <div class="tags-actions"><button type="submit">Sök</button><a href="<?= h($url('')) ?>">Rensa filter</a></div>
      <label for="tag-sort">Sortera taggar<select id="tag-sort" name="sort"><?php foreach (['alpha'=>'Alfabetiskt (A–Ö)', 'map'=>'Karta/katalog (A–Ö)', 'count'=>'Antal förmågor (flest först)'] as $key=>$label): ?><option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
      <label for="tag-limit">Taggar per sida<select id="tag-limit" name="limit"><?php foreach ([30,60,100] as $size): ?><option <?= $limit === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></label>
    </div>
    <?php if ($tag !== ''): ?><input type="hidden" name="tag" value="<?= h($tag) ?>"><p>Vald tagg: <strong><?= h($tag) ?></strong></p><?php endif; ?>
  </form>
  <details class="tags-browser" <?= $tag === '' || $param('page') !== '' ? 'open' : '' ?>>
    <summary id="tags-heading">Välj tagg <span>Visa eller dölj taggar</span></summary>
    <p><?= $tagCount ? (($page - 1) * $limit + 1) : 0 ?>–<?= min($page * $limit, $tagCount) ?> av <?= $tagCount ?> <?= $sort === 'map' ? 'taggar per karta' : 'taggar' ?></p><div class="tag-links">
    <?php $lastMap = null; foreach ($tags as $item): ?>
      <?php if ($sort === 'map' && $lastMap !== $item['map']): $lastMap = $item['map']; ?><h3 class="tag-group-heading"><?= h($item['label']) ?></h3><?php endif; ?>
      <a href="<?= h($sort === 'map' ? $pageUrl(['tag'=>$item['name'], 'map'=>$item['map'], 'q'=>'']) : $url($item['name'])) ?>" <?= $fold($item['name']) === $fold($tag) ? 'aria-current="true"' : '' ?>><?= h($item['name']) ?> <span><?= $item['count'] ?></span></a>
    <?php endforeach; ?>
    <?php if (!$tags): ?><p>Inga taggar hittades.</p><?php endif; ?>
    </div>
    <?php if ($pages > 1): ?><nav class="tag-pagination" aria-label="Sidor med taggar">
      <?php if ($page > 1): ?><a href="<?= h($pageUrl(['page'=>$page-1, 'result_page'=>$resultPage])) ?>#tags-heading">← Föregående</a><?php endif; ?>
      <span>Sida <?= $page ?> av <?= $pages ?></span>
      <?php if ($page < $pages): ?><a href="<?= h($pageUrl(['page'=>$page+1, 'result_page'=>$resultPage])) ?>#tags-heading">Nästa →</a><?php endif; ?>
    </nav><?php endif; ?>
  </details>
  <section id="results" aria-labelledby="results-heading">
    <h2 id="results-heading"><?= $rowCount ?> förmågor<?= $tag !== '' ? ' med taggen ' . h($tag) : ' med taggar' ?></h2>
    <?php if (!$rows): ?><p class="empty-state">Inga förmågor matchar filtret. Prova en annan tagg eller karta.</p><?php endif; ?>
    <ul class="tag-results">
    <?php foreach ($rows as $row): $cap = $row['cap'];
      $maturity = (int)$cap->get('maturity', 0);
      if ($maturity < 1 || $maturity > 5) $maturity = 0;
    ?>
      <li><article class="maturity-<?= $maturity ?>">
        <div class="tag-result-meta"><?= h($row['label']) ?> · <?= h($tax['layer_display_names'][$cap->layer] ?? $tax['layers'][$cap->layer] ?? $cap->layer) ?> · <?= h($cap->id) ?></div>
        <h3><a href="<?= h(base_path('view/capability.php?' . http_build_query(['id'=>$cap->id, 'map'=>$row['map']], '', '&', PHP_QUERY_RFC3986))) ?>"><?= h($cap->name) ?> <span aria-hidden="true">→</span></a></h3>
        <?php if ($cap->description !== ''): ?><p><?= h($cap->description) ?></p><?php endif; ?>
        <div class="tag-result-status"><span class="status-badge"><?= h($maturityLabels[$maturity]) ?></span></div>
        <div class="tag-links"><?php foreach ($row['tags'] as $value): ?><a href="<?= h($url($value)) ?>" <?= $fold($value) === $fold($tag) ? 'aria-current="true"' : '' ?>><?= h($value) ?></a><?php endforeach; ?></div>
      </article></li>
    <?php endforeach; ?>
    </ul>
    <?php if ($resultPages > 1): ?><nav class="tag-pagination" aria-label="Sidor med förmågor">
      <?php if ($resultPage > 1): ?><a href="<?= h($pageUrl(['page'=>$page, 'result_page'=>$resultPage-1])) ?>#results">← Föregående</a><?php endif; ?>
      <span>Sida <?= $resultPage ?> av <?= $resultPages ?> · 30 förmågor per sida</span>
      <?php if ($resultPage < $resultPages): ?><a href="<?= h($pageUrl(['page'=>$page, 'result_page'=>$resultPage+1])) ?>#results">Nästa →</a><?php endif; ?>
    </nav><?php endif; ?>
  </section>
</main>
</body>
</html>
