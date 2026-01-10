<?php
require __DIR__ . '/../app/bootstrap.php';

use App\CapabilityRepository;

$app = cfg('app');
$tax = cfg('taxonomy');

// Check export scope: 'current' (default) or 'all'
$scope = $_GET['scope'] ?? 'current';

// Get selected content directory
$selectedKey = get_selected_content_key();
$selectedDir = get_content_dir();
$contentDirs = get_content_dirs();

if ($scope === 'all') {
    // Export from all content directories
    $caps = [];
    foreach ($contentDirs as $key => $dirInfo) {
        $repo = new CapabilityRepository($dirInfo['path']);
        $dirCaps = $repo->all();
        // Add directory info to each capability for reference
        foreach ($dirCaps as $cap) {
            $cap->_source_dir = $dirInfo['label'] ?? $key;
        }
        $caps = array_merge($caps, $dirCaps);
    }
    $dirLabel = 'alla_kataloger';
} else {
    // Export only from current directory
    $repo = new CapabilityRepository($selectedDir);
    $caps = $repo->all();
    $dirLabel = $contentDirs[$selectedKey]['label'] ?? $selectedKey;
}

usort($caps, fn($a,$b) => strcmp($a->id, $b->id));

// Include scope info in filename
$filename = 'formagekarta_' . $dirLabel . '_' . date('Y-m-d') . '.xls';

// Excel can open HTML tables when served as xls
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

function cell($v){
  if (is_array($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<html>
<head><meta charset="utf-8"></head>
<body>
<table border="1" cellspacing="0" cellpadding="4">
  <thead style="font-weight:bold;background:#eee;">
    <tr>
      <th>ID</th>
      <th>Namn</th>
      <th>Layer</th>
      <th>Area</th>
      <th>Beskrivning</th>
      <th>Owner</th>
      <th>Status</th>
      <th>Maturity</th>
      <th>Tags</th>
      <th>Updated</th>
      <th>Source</th>
      <?php if ($scope === 'all'): ?>
      <th>Katalog</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
  <?php foreach($caps as $c): ?>
    <tr>
      <td><?= cell($c->id) ?></td>
      <td><?= cell($c->name) ?></td>
      <td><?= cell($tax['layers'][$c->layer] ?? $c->layer) ?></td>
      <td><?= cell($c->area) ?></td>
      <td><?= cell($c->description) ?></td>
      <td><?= cell($c->get('owner','')) ?></td>
      <td><?= cell($c->get('status','')) ?></td>
      <td><?= cell($c->get('maturity','')) ?></td>
      <td><?= cell($c->get('tags','')) ?></td>
      <td><?= cell($c->get('updated','')) ?></td>
      <td><?= cell($c->path ?? '') ?></td>
      <?php if ($scope === 'all'): ?>
      <td><?= cell($c->_source_dir ?? '') ?></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>