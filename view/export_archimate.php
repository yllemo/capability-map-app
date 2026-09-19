<?php
require __DIR__ . '/../app/bootstrap.php';

use App\ArchimateExport;
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

$modelName = 'Förmågekarta – ' . ($contentDirs[$selectedKey]['label'] ?? $dirLabel);
$xml = ArchimateExport::build($caps, $tax, $modelName, $scope === 'all');

$filename = 'formagekarta_' . $dirLabel . '_' . date('Y-m-d') . '.archimate.xml';

header('Content-Type: application/xml; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo $xml;
