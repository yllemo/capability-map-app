<?php
require __DIR__ . '/../app/bootstrap.php';

use App\CapabilityRepository;

$tax = cfg('taxonomy');

$scope = $_GET['scope'] ?? 'current';

$selectedKey = get_selected_content_key();
$selectedDir = get_content_dir();
$contentDirs = get_content_dirs();

if ($scope === 'all') {
  $caps = [];
  foreach (readable_content_dirs() as $key => $dirInfo) {
    $repo = new CapabilityRepository($dirInfo['path']);
    $dirCaps = $repo->all();
    foreach ($dirCaps as $cap) {
      $cap->_source_dir = $dirInfo['label'] ?? $key;
    }
    $caps = array_merge($caps, $dirCaps);
  }
  $dirLabel = 'alla_kataloger';
} else {
  require_read($selectedKey);
  $repo = new CapabilityRepository($selectedDir);
  $caps = $repo->all();
  $dirLabel = $contentDirs[$selectedKey]['label'] ?? $selectedKey;
}

usort($caps, fn($a, $b) => strcmp($a->id, $b->id));

$filename = 'formagekarta_' . $dirLabel . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

function csv_value($v): string {
  if (is_array($v)) return implode('; ', array_map('strval', $v));
  return (string)$v;
}

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel detects encoding correctly

$headers = ['ID', 'Namn', 'Layer', 'Area', 'Beskrivning', 'Owner', 'Status', 'Maturity', 'Criticality', 'Risk', 'Tags', 'Beroenden', 'Uppdaterad', 'Källfil'];
if ($scope === 'all') $headers[] = 'Katalog';
fputcsv($out, $headers, ';');

foreach ($caps as $c) {
  $row = [
    $c->id,
    $c->name,
    $tax['layers'][$c->layer] ?? $c->layer,
    $c->area,
    $c->description,
    csv_value($c->get('owner', '')),
    csv_value($c->get('status', '')),
    csv_value($c->get('maturity', '')),
    csv_value($c->get('criticality', '')),
    csv_value($c->get('risk_level', '')),
    csv_value($c->get('tags', '')),
    csv_value($c->get('dependencies', '')),
    csv_value($c->get('updated', '')),
    $c->path ?? '',
  ];
  if ($scope === 'all') $row[] = $c->_source_dir ?? '';
  fputcsv($out, $row, ';');
}

fclose($out);
