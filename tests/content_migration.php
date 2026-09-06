<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/ContentMigration.php';
use App\ContentMigration;
function checkMigration(bool $ok, string $message): void {
  if (!$ok) throw new RuntimeException($message);
}
$project = sys_get_temp_dir() . '/capmap-migration-test-' . bin2hex(random_bytes(8));
mkdir($project);
try {
  foreach (['config', 'storage', 'content', 'content2'] as $folder) mkdir($project . '/' . $folder);
  mkdir($project . '/content/area');
  file_put_contents($project . '/content/area/test.md', "---\nname: Åäö\n---\nText");
  file_put_contents($project . '/content2/source.json', '{"organization":"Test"}');
  $dirs = ['content' => ['path' => $project . '/content', 'label' => 'Huvudkarta'], 'second' => ['path' => $project . '/content2', 'label' => 'Alternativ']];
  $migration = new ContentMigration($project);
  $plan = $migration->plan($dirs);
  checkMigration($plan['second']['folder'] === 'content2', 'Katalognamn ska bevaras även när nyckeln skiljer sig.');
  try {
    $migration->plan(['a' => $dirs['content'], 'b' => $dirs['content']]);
    throw new LogicException('Dubbla sökvägar accepterades.');
  } catch (RuntimeException $e) {}
  $migration->run($dirs);
  checkMigration(file_get_contents($project . '/content/content/area/test.md') === "---\nname: Åäö\n---\nText", 'Markdown ska flyttas oförändrad.');
  checkMigration(file_get_contents($project . '/content/content2/source.json') === '{"organization":"Test"}', 'Även andra filer ska flyttas.');
  checkMigration(!file_exists($project . '/content2'), 'Den gamla katalogen ska vara flyttad.');
  $config = json_decode(file_get_contents($project . '/config/content.local.json'), true);
  checkMigration($config['content_root'] === 'content' && $config['content_dirs']['second']['folder'] === 'content2', 'Konfigurationen ska använda relativa mappar under roten.');
  try {
    $migration->run($dirs);
    throw new LogicException('En andra migrering accepterades.');
  } catch (RuntimeException $e) {}
  echo "Content migration tests passed\n";
} finally {
  // Only remove the uniquely named test workspace created above.
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($project);
}
