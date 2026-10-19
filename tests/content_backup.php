<?php
declare(strict_types=1);
foreach (['ContentBackup','ContentMigration','ContentFolders','Frontmatter','Capability','CapabilityRepository','CapabilityReference'] as $class) require __DIR__ . '/../app/lib/' . $class . '.php';
use App\ContentBackup;
function backupCheck(bool $ok, string $why): void { if (!$ok) throw new RuntimeException($why); }
function expectBackupFailure(callable $call, string $why): void {
  try { $call(); } catch (RuntimeException $e) { return; }
  throw new LogicException($why);
}
$root = sys_get_temp_dir() . '/capmap-backup-test-' . bin2hex(random_bytes(8));
mkdir($root . '/source/maps/a', 0775, true);
mkdir($root . '/source/maps/b', 0775, true);
mkdir($root . '/source/maps/empty', 0775, true);
mkdir($root . '/target/content', 0775, true);
mkdir($root . '/target/custom-data', 0775, true);
$source = $root . '/source';
$project = $root . '/target';
$original = "---\nid: original\nname: Original\n---\n# Förmåga\n\nInnehåll\n";
file_put_contents($source . '/maps/a/original.md', $original);
file_put_contents($source . '/maps/a/attachment.bin', "\x00\x01\xff");
file_put_contents($source . '/maps/b/link.md', "---\nid: linked\nredirect_map: a\nredirect_id: original\n---\n");
file_put_contents($source . '/notes.txt', 'Root attachment');
file_put_contents($source . '/.capmap-config.json', '{}');
file_put_contents($source . '/.capmap-admin.php', '<?php throw new Exception("must not execute");');
file_put_contents($source . '/maps/a/test.mcp.lock', 'skip');
mkdir($source . '/.capmap-import-old');
file_put_contents($source . '/.capmap-import-old/partial.md', 'skip');
$dirs = [];
foreach (['a','b','empty'] as $key) $dirs[$key] = ['path'=>$source . '/maps/' . $key,'label'=>'Karta ' . $key];
$settings = ['app'=>['site_name'=>'Återställd webbplats','map_order'=>['b','a','empty']], 'users'=>['backup-admin'=>['name'=>'Admin','role'=>'admin','password_hash'=>password_hash('test-only-password', PASSWORD_DEFAULT)]], 'mcp'=>['token_user'=>'backup-admin','token_sha256'=>hash('sha256','fixture-token')]];
$archive = $root . '/backup.zip';
try {
  (new ContentBackup($source))->export($archive, $dirs, $settings);
  $zip = new ZipArchive(); $zip->open($archive);
  $manifest = json_decode($zip->getFromName('manifest.json'), true);
  backupCheck(count($manifest['files']) === 4, 'Only data files and root attachment exported');
  backupCheck($zip->locateName('maps/a/test.mcp.lock') === false, 'Lock files excluded');
  backupCheck($zip->locateName('root/.capmap-admin.php') === false, 'Managed PHP serialized as JSON settings');
  $zip->close();
  file_put_contents($project . '/custom-data/notes.txt', 'Old attachment');
  file_put_contents($project . '/custom-data/old.md', 'Old capability');
  $result = (new ContentBackup($project . '/custom-data'))->restore($archive, $project, 'overwrite', true);
  backupCheck($result === ['maps'=>3,'files'=>4,'preserved_files'=>0,'settings_restored'=>true], 'Restore counts');
  $config = json_decode(file_get_contents($project . '/content/.capmap-config.json'), true);
  backupCheck(realpath($config['content_root']) === realpath($project . '/custom-data'), 'Configured content root retained');
  $restored = [];
  foreach ($config['content_dirs'] as $key=>$dir) $restored[$key] = $dir + ['path'=>$config['content_root'] . '/' . $dir['folder']];
  backupCheck(file_get_contents($restored['a']['path'] . '/original.md') === $original, 'Markdown preserved byte for byte');
  backupCheck(file_get_contents($restored['a']['path'] . '/attachment.bin') === "\x00\x01\xff", 'Binary attachment preserved');
  backupCheck(file_get_contents($project . '/custom-data/notes.txt') === 'Root attachment', 'Root file restored');
  backupCheck(file_get_contents($project . '/custom-data/old.md') === 'Old capability', 'Existing maps remain on disk');
  backupCheck(is_dir($restored['empty']['path']), 'Empty map restored');
  backupCheck((require $project . '/content/.capmap-admin.php') === $settings, 'Admin settings round trip');
  backupCheck((new App\CapabilityRepository($restored['b']['path'], $restored))->all()[0]->name === 'Original', 'Linked capability resolves after restore');
  backupCheck(!glob($project . '/custom-data/.capmap-import-*'), 'Staging directory cleaned after activation');
  (new ContentBackup($project . '/custom-data'))->export($root . '/second.zip', $restored, $settings);
  $zip->open($root . '/second.zip');
  $second = json_decode($zip->getFromName('manifest.json'), true); $zip->close();
  backupCheck(isset($second['files']['maps/a/original.md']), 'Restored maps can be exported again');

  // Malformed archives must leave live files and settings untouched.
  $before = file_get_contents($project . '/content/.capmap-config.json');
  foreach (['traversal','php','double-extension','reserved-config','checksum','duplicate','symlink','settings','missing','oversize'] as $attack) {
    $bad = $root . '/bad-' . $attack . '.zip'; copy($archive, $bad);
    $zip->open($bad);
    if ($attack === 'traversal') $zip->addFromString('../escape.md', 'malicious');
    if (in_array($attack, ['php','double-extension','reserved-config'], true)) {
      $text = '<?php echo "unsafe";';
      $name = $attack === 'php' ? 'maps/a/shell.php' : ($attack === 'double-extension' ? 'maps/a/shell.php.txt' : 'root/.CAPMAP-CONFIG.JSON');
      $manifestBad = $manifest;
      $manifestBad['files'][$name] = ['size'=>strlen($text),'sha256'=>hash('sha256',$text)];
      $zip->addFromString($name, $text); $zip->addFromString('manifest.json', json_encode($manifestBad));
    }
    if ($attack === 'checksum') $zip->addFromString('maps/a/original.md', str_repeat('x', strlen($original)));
    if ($attack === 'duplicate') $zip->addFromString('maps/a/ORIGINAL.md', $original);
    if ($attack === 'symlink') $zip->setExternalAttributesName('maps/a/original.md', 3, 0120777 << 16);
    if ($attack === 'settings') { $manifestBad = $manifest; $manifestBad['settings']['app']['content_dir'] = '/outside'; $zip->addFromString('manifest.json', json_encode($manifestBad)); }
    if ($attack === 'missing') $zip->deleteName('maps/a/original.md');
    if ($attack === 'oversize') $zip->addFromString('maps/a/original.md', str_repeat('x', 26214401));
    $zip->close();
    expectBackupFailure(fn()=>(new ContentBackup($project . '/custom-data'))->restore($bad, $project), $attack . ' accepted');
    backupCheck(file_get_contents($project . '/content/.capmap-config.json') === $before, $attack . ' changed active config');
    backupCheck(!glob($project . '/custom-data/.capmap-import-*'), $attack . ' left staging files');
  }
  // Fail during activation: undo root-file replacement and published map directories.
  mkdir($root . '/broken/content/.capmap-config.json', 0775, true);
  mkdir($root . '/broken/data');
  file_put_contents($root . '/broken/data/notes.txt', 'Keep original');
  expectBackupFailure(fn()=>(new ContentBackup($root . '/broken/data'))->restore($archive, $root . '/broken', 'overwrite', true), 'Activation should fail');
  backupCheck(file_get_contents($root . '/broken/data/notes.txt') === 'Keep original', 'Root attachment rollback');
  backupCheck(!glob($root . '/broken/data/capmap-restored-*'), 'Published maps rolled back');
  backupCheck(!is_file($root . '/broken/content/.capmap-admin.php'), 'Admin settings rolled back');
  backupCheck(!glob($root . '/broken/data/.capmap-import-*'), 'Failed import cleaned up');
  App\ContentFolders::manage($project, 'a', 'rename', ['label'=>'Bytt namn','folder'=>'renamed-map']);
  backupCheck(is_file($project . '/custom-data/renamed-map/original.md'), 'Imported map remains manageable under a custom root');
  App\ContentFolders::create($project, ['content_root'=>$project . '/custom-data'], 'new-map', 'New map', '');
  backupCheck(is_dir($project . '/custom-data/new-map'), 'New maps use restored custom content root');
  // Legacy installations may use the whole content root as a map.
  (new ContentBackup($source))->export($root . '/legacy.zip', ['whole'=>['path'=>$source,'label'=>'Whole root']], $settings);
  $zip->open($root . '/legacy.zip');
  backupCheck($zip->getFromName('root/notes.txt') === 'Root attachment', 'Root files retained at their original location when root is also a map');
  $zip->close();
  mkdir($root . '/legacy/content', 0775, true);
  (new ContentBackup($root . '/legacy/content'))->restore($root . '/legacy.zip', $root . '/legacy', 'overwrite', true);
  $legacyConfig = json_decode(file_get_contents($root . '/legacy/content/.capmap-config.json'), true);
  backupCheck($legacyConfig['content_root'] === 'content', 'Default content root remains compatible with folder management');
  backupCheck(file_get_contents($root . '/legacy/content/notes.txt') === 'Root attachment', 'Legacy root attachment restored in place');
  mkdir($root . '/keep/content/a', 0775, true);
  mkdir($root . '/keep/content/current', 0775, true);
  $kept = "---\nid: original\nname: Keep existing\n---\nExisting text\n";
  file_put_contents($root . '/keep/content/a/renamed.md', $kept);
  file_put_contents($root . '/keep/content/a/attachment.bin', 'Existing attachment');
  file_put_contents($root . '/keep/content/current/unique.md', "---\nid: unique\nname: Existing map\n---\n");
  file_put_contents($root . '/keep/content/notes.txt', 'Existing root file');
  file_put_contents($root . '/keep/content/.capmap-admin.php', '<?php return ["app"=>["site_name"=>"Keep settings"]];');
  $currentDirs = ['a'=>['path'=>$root . '/keep/content/a','label'=>'Keep label'], 'current'=>['path'=>$root . '/keep/content/current','label'=>'Current map']];
  $keptResult = (new ContentBackup($root . '/keep/content'))->restore($archive, $root . '/keep', 'keep', false, $currentDirs);
  $keepConfig = json_decode(file_get_contents($root . '/keep/content/.capmap-config.json'), true);
  $keepA = $root . '/keep/content/' . $keepConfig['content_dirs']['a']['folder'];
  backupCheck($keptResult['preserved_files'] === 3, 'Keep mode reports root, filename and capability-ID conflicts');
  backupCheck(file_get_contents($keepA . '/renamed.md') === $kept && !file_exists($keepA . '/original.md'), 'Keep mode preserves original capability by ID even if filename differs');
  backupCheck(file_get_contents($keepA . '/attachment.bin') === 'Existing attachment', 'Keep mode preserves same filename');
  backupCheck(file_get_contents($root . '/keep/content/notes.txt') === 'Existing root file', 'Keep mode preserves root files');
  backupCheck(isset($keepConfig['content_dirs']['current'], $keepConfig['content_dirs']['b']), 'Keep mode retains existing maps and adds new ones');
  backupCheck((require $root . '/keep/content/.capmap-admin.php')['app']['site_name'] === 'Keep settings', 'Settings unchanged when checkbox is off');
  backupCheck($keepConfig['content_dirs']['a']['label'] === 'Keep label', 'Keep mode preserves existing map metadata');
  echo "Content backup tests passed\n";
} finally {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
  foreach ($it as $entry) { if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname()); else unlink($entry->getPathname()); }
  rmdir($root);
}
