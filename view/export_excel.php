<?php
require __DIR__ . '/../app/bootstrap.php';

use App\CapabilityRepository;

$app = cfg('app');
$tax = cfg('taxonomy');

$repo = new CapabilityRepository($app['content_dir']);
$caps = $repo->all();

usort($caps, fn($a,$b) => strcmp($a->id, $b->id));

$filename = 'formagekarta_' . date('Y-m-d') . '.xls';

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
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>