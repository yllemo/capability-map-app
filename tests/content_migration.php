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
  // No config/ or storage/; the app root is read-only on Unix, like an image.
  foreach (['content', 'content2'] as $folder) mkdir($project . '/' . $folder);
  mkdir($project . '/content/area');
  file_put_contents($project . '/content/area/test.md', "---\nname: Åäö\n---\nText");
  file_put_contents($project . '/content2/source.json', '{"organization":"Test"}');
  if (DIRECTORY_SEPARATOR === '/') {
    chmod($project . '/content2/source.json', 0444);
    chmod($project, 0555);
  }
  $dirs = ['content' => ['path' => $project . '/content', 'label' => 'Huvudkarta'], 'second' => ['path' => $project . '/content2', 'label' => 'Alternativ']];
  $migration = new ContentMigration($project);
  $plan = $migration->plan($dirs);
  checkMigration($plan['second']['folder'] === 'content2', 'Katalognamn ska bevaras även när nyckeln skiljer sig.');
  try {
    $migration->plan(['a' => $dirs['content'], 'b' => $dirs['content']]);
    throw new LogicException('Dubbla sökvägar accepterades.');
  } catch (RuntimeException $e) {}
  $warnings = $migration->run($dirs);
  checkMigration($warnings === [], 'Migreringen ska inte lämna ostädade original.');
  checkMigration(file_get_contents($project . '/content/content/area/test.md') === "---\nname: Åäö\n---\nText", 'Markdown ska flyttas oförändrad.');
  checkMigration(file_get_contents($project . '/content/content2/source.json') === '{"organization":"Test"}', 'Även andra filer ska flyttas.');
  checkMigration(is_dir($project . '/content2'), 'Den monterade källkatalogen måste bevaras.');
  checkMigration(scandir($project . '/content2') === ['.', '..'], 'Källkatalogen ska vara tom.');
  checkMigration(!file_exists($project . '/content/area'), 'Gamla filer och undermappar i /content ska städas.');
  checkMigration(!file_exists($project . '/content/content/content'), 'Källan får inte kopieras rekursivt in i sig själv.');
  checkMigration(!file_exists($project . '/content/content/.capmap-migration.lock'), 'Låsfilen ska inte kopieras.');
  $config = json_decode(file_get_contents($project . '/content/.capmap-config.json'), true);
  checkMigration($config['content_root'] === 'content' && $config['content_dirs']['second']['folder'] === 'content2', 'Konfigurationen ska använda relativa mappar under roten.');
  try {
    $migration->run($dirs);
    throw new LogicException('En andra migrering accepterades.');
  } catch (RuntimeException $e) {}
  echo "Content migration tests passed\n";
} finally {
  if (DIRECTORY_SEPARATOR === '/') {
    chmod($project, 0755);
    chmod($project . '/content2', 0755);
    if (file_exists($project . '/content2/source.json')) chmod($project . '/content2/source.json', 0644);
  }
  // Only remove the uniquely named test workspace created above.
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($project);
}
