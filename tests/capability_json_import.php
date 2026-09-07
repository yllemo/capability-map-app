<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/CapabilityJsonImport.php';
require __DIR__ . '/../app/lib/Frontmatter.php';
require __DIR__ . '/../app/lib/Capability.php';
require __DIR__ . '/../app/lib/CapabilityRepository.php';

use App\CapabilityJsonImport;
use App\Frontmatter;

function check(bool $condition, string $message): void {
  if (!$condition) throw new RuntimeException($message);
}
$items = [];
foreach (['initial', 'developing', 'defined', 'managed', 'optimized'] as $i => $status) {
  $items[] = ['id' => $i + 1, 'title' => 'Ärende "ett"', 'description' => "Svensk text: åäö\nNy rad", 'status' => $status, 'layer' => ['strategic', 'core', 'support'][$i % 3], 'url' => 'example.com/test'];
}
$source = ['organization' => 'Göteborg', 'description' => 'Kartbeskrivning', 'capabilities' => $items];
$result = CapabilityJsonImport::convert(json_encode($source));
check(count($result['files']) === 5, 'Alla förmågor ska importeras');
foreach (array_values($result['files']) as $i => $markdown) {
  $parsed = Frontmatter::parse($markdown);
  check($parsed['meta']['name'] === 'Ärende "ett"', 'Citat och svenska tecken ska bevaras');
  check((int)$parsed['meta']['maturity'] === $i + 1, 'Mognad ska mappas');
  check($parsed['meta']['layer'] === ['ledning_styrning', 'karnprocesser', 'verksamhetsstod'][$i % 3], 'Skikt ska mappas');
  check($parsed['meta']['url'] === 'https://example.com/test', 'URL ska normaliseras');
  check(str_contains($parsed['body'], "Svensk text: åäö\nNy rad"), 'Brödtext ska bevaras');
}
check($result['source'] === $source, 'Originalets kartmetadata ska bevaras');
check($result['directory'] === CapabilityJsonImport::convert(json_encode($source, JSON_PRETTY_PRINT))['directory'], 'Whitespace ska inte påverka importidentiteten');
$bad = [$source, $source, $source, $source];
unset($bad[0]['capabilities'][0]['title']);
$bad[1]['capabilities'][0]['layer'] = 'unknown';
$bad[2]['capabilities'][0]['url'] = 'javascript:alert(1)';
$bad[3]['capabilities'][1]['id'] = 1;
foreach (array_merge(['{', '{"capabilities":[]}'], array_map('json_encode', $bad)) as $json) {
  try {
    CapabilityJsonImport::convert($json);
  } catch (InvalidArgumentException $e) {
    continue;
  }
  throw new RuntimeException('Ogiltiga indata ska avvisas');
}
$directory = sys_get_temp_dir() . '/capmap-json-test-' . bin2hex(random_bytes(8));
mkdir($directory);
try {
  mkdir($directory . '/.capmap-import-interrupted');
  file_put_contents($directory . '/.capmap-import-interrupted/partial.md', "---\nid: partial\nname: Partial\n---\n");
  $repo = new App\CapabilityRepository($directory);
  check($repo->all() === [], 'Ofullständiga importer ska inte visas.');
  CapabilityJsonImport::save($result, $directory);
  check(count($repo->all()) === 5, 'Importerade filer ska bli tillgängliga i målkartan.');
  check(is_file($directory . '/' . $result['directory'] . '/source.json'), 'Originalinformationen ska sparas.');
  try {
    CapabilityJsonImport::save($result, $directory);
    throw new LogicException('Återimport får inte skriva över filer.');
  } catch (RuntimeException $e) {}
} finally {
  // Only the uniquely named temporary test directory is removed.
  $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($iterator as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($directory);
}
echo "JSON import tests passed\n";
