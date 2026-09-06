<?php
declare(strict_types=1);
foreach (['Frontmatter', 'Capability', 'CapabilityRepository', 'CapabilityReference'] as $class) require __DIR__ . '/../app/lib/' . $class . '.php';
use App\CapabilityReference;
use App\CapabilityRepository;

function referenceCheck(bool $value, string $message): void {
  if (!$value) throw new RuntimeException($message);
}
$root = sys_get_temp_dir() . '/capmap-reference-test-' . bin2hex(random_bytes(8));
mkdir($root . '/a', 0775, true);
mkdir($root . '/b');
$dirs = ['a' => ['path' => $root . '/a'], 'b' => ['path' => $root . '/b']];
try {
  $original = "---\nid: cap-original\nname: Original\nlayer: karnprocesser\narea: IT\nmaturity: 4\ntags: [test]\n---\nBody\n";
  file_put_contents($root . '/b/original.md', $original);
  $reference = CapabilityReference::markdown('cap-ref-test', '["b","cap-original"]', $dirs);
  $meta = App\Frontmatter::parse($reference)['meta'];
  referenceCheck(array_keys($meta) === ['id', 'redirect_map', 'redirect_id'], 'Referensfilen ska vara minimal.');
  file_put_contents($root . '/a/ref.md', $reference);
  $repo = new CapabilityRepository($root . '/a', $dirs);
  $card = $repo->all()[0];
  referenceCheck($card->id === 'cap-ref-test' && $card->name === 'Original' && $card->get('maturity') === 4, 'Kortet ska hämta originalets innehåll men ha eget ID.');
  file_put_contents($root . '/b/original.md', str_replace('name: Original', 'name: Ändrat namn', $original));
  referenceCheck($repo->all()[0]->name === 'Ändrat namn', 'Ändringar i originalet måste slå igenom.');
  $target = CapabilityReference::resolve($meta, $dirs);
  referenceCheck($target['map'] === 'b' && $target['cap']->id === 'cap-original', 'Målet ska inkludera rätt karta och ID.');
  unlink($root . '/b/original.md');
  referenceCheck($repo->all()[0]->name === 'Bruten referens', 'Borttaget mål ska hanteras.');
  file_put_contents($root . '/b/original.md', "---\nid: cap-original\nredirect_map: a\nredirect_id: cap-ref-test\n---\n");
  try {
    CapabilityReference::resolve($meta, $dirs);
    throw new LogicException('Omstyrningsloop accepterades.');
  } catch (RuntimeException $e) {}
  referenceCheck($repo->all()[0]->name === 'Bruten referens', 'Loop får inte krascha kartvyn.');
  echo "Capability reference tests passed\n";
} finally {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($root);
}
