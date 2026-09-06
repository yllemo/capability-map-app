<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/ContentMigration.php';
require __DIR__ . '/../app/lib/ContentFolders.php';
use App\ContentFolders;

function checkFolder(bool $ok, string $message): void {
  if (!$ok) throw new RuntimeException($message);
}
$project = sys_get_temp_dir() . '/capmap-folders-' . bin2hex(random_bytes(8));
mkdir($project . '/content/existing', 0775, true);
$app = ['content_dirs' => ['original' => ['path' => $project . '/content/existing', 'label' => 'Befintlig', 'description' => 'Behåll', 'custom' => 'metadata']]];
try {
  // Manual migration: bootstrap local JSON from PHP-configured nested folders.
  ContentFolders::create($project, $app, 'new-map', 'Ny "karta"', 'Åäö');
  $path = $project . '/content/.capmap-config.json';
  $first = json_decode(file_get_contents($path), true);
  checkFolder($first['content_dirs']['original']['custom'] === 'metadata', 'Befintlig metadata tappades.');
  checkFolder($first['content_dirs']['original']['folder'] === 'existing', 'Befintlig sökväg mappades fel.');
  checkFolder($first['content_dirs']['new-map']['label'] === 'Ny "karta"', 'Text bevarades inte.');
  // Stale app config must not overwrite maps created by a previous request.
  ContentFolders::create($project, $app, 'second-map', 'Andra', '');
  $saved = file_get_contents($path);
  checkFolder(count(json_decode($saved, true)['content_dirs']) === 3, 'Kartor skrevs över.');
  foreach (['new-map', 'existing', '../escape'] as $key) {
    try {
      ContentFolders::create($project, $app, $key, 'Test', '');
      throw new LogicException('Ogiltig eller upptagen nyckel accepterades.');
    } catch (RuntimeException | InvalidArgumentException $e) {}
    checkFolder(file_get_contents($path) === $saved, 'Avvisat anrop ändrade konfigurationen.');
  }
  // A blocked config destination must roll back only the newly created folder.
  unlink($path);
  mkdir($path);
  try {
    ContentFolders::create($project, $app, 'rollback', 'Test', '');
    throw new LogicException('Skrivfel accepterades.');
  } catch (RuntimeException $e) {}
  checkFolder(!file_exists($project . '/content/rollback'), 'Ny katalog återställdes inte.');
  checkFolder(is_dir($project . '/content/existing'), 'Befintlig katalog ändrades.');
  echo "Content folder tests passed\n";
} finally {
  // Only the uniquely named fixture created by this test is removed.
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $entry) { if ($entry->isDir()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($project);
}
