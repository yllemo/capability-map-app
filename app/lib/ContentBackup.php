<?php
declare(strict_types=1);
namespace App;

/** Portable data backups. Never execute PHP or trust absolute paths from an archive. */
final class ContentBackup {
  private const MAX_FILES = 20000;
  private const MAX_BYTES = 262144000;
  private const MAX_FILE_BYTES = 26214400;
  private string $root;
  public function __construct(string $root) {
    $resolved = realpath($root);
    if ($resolved === false || !is_dir($resolved)) throw new \RuntimeException('Innehållsroten finns inte.');
    $this->root = $resolved;
  }
  private static function zip(): \ZipArchive {
    if (!class_exists(\ZipArchive::class)) throw new \RuntimeException('PHP-tillägget ZipArchive (zip) måste vara aktiverat.');
    return new \ZipArchive();
  }
  private static function safeName(string $name): void {
    if ($name === '' || strlen($name) > 1024 || preg_match('~[\\\\:\x00-\x1f\x7f]~', $name) || str_starts_with($name, '/')) throw new \RuntimeException('Ogiltig sökväg i ZIP-filen.');
    foreach (explode('/', $name) as $part) {
      if ($part === '' || $part === '.' || $part === '..' || rtrim($part, '. ') !== $part || preg_match('/^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/iD', $part)) throw new \RuntimeException('Ogiltigt filnamn i ZIP-filen.');
    }
  }
  private static function dataFile(string $name): void {
    self::safeName($name);
    if (preg_match('/\.(?:php\d*|phtml|pht|phar|cgi|pl|asp|aspx|jsp|sh|exe|dll|bat|cmd|ps1)(?:\.|$)/i', basename($name))
        || in_array(strtolower(basename($name)), ['.htaccess', '.user.ini', 'web.config'], true)) throw new \RuntimeException('Körbara filer och webbserverkonfiguration får inte importeras: ' . $name);
  }
  private static function transient(string $name): bool {
    $name = strtolower($name);
    return str_starts_with($name, '.capmap-import-') || str_starts_with($name, '.capmap-migration')
      || str_starts_with($name, 'capmap-restored-') || str_ends_with($name, '.lock') || str_ends_with($name, '.tmp');
  }
  private function inventory(string $root, array $exclude = []): array {
    $root = realpath($root);
    if ($root === false) throw new \RuntimeException('En kartkatalog saknas.');
    $out = [];
    $walk = function(string $dir, string $prefix) use (&$walk, &$out, $exclude): void {
      $names = scandir($dir);
      if ($names === false) throw new \RuntimeException('Kunde inte läsa innehållskatalogen.');
      foreach ($names as $name) {
        if ($name === '.' || $name === '..' || self::transient($name)) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (in_array(realpath($path), $exclude, true)) continue;
        $relative = $prefix . $name;
        if (is_link($path)) throw new \RuntimeException('Symboliska länkar stöds inte i backup: ' . $relative);
        if (is_dir($path)) { $walk($path, $relative . '/'); continue; }
        if (!is_file($path) || !is_readable($path)) throw new \RuntimeException('En innehållsfil kan inte läsas.');
        if (in_array(strtolower($name), ['.capmap-admin.php', '.capmap-config.json'], true)) continue;
        self::dataFile($relative);
        $out[$relative] = $path;
      }
    };
    $walk($root, '');
    return $out;
  }
  public function export(string $zipPath, array $dirs, array $settings): void {
    if (!$dirs || count($dirs) > 1000) throw new \RuntimeException('Backupen måste innehålla 1–1000 kartor.');
    $zip = self::zip();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Kunde inte skapa ZIP-filen.');
    try {
      $manifest = ['format'=>'capability-map-backup','version'=>1,'created_at'=>gmdate('c'),'maps'=>[], 'files'=>[],'settings'=>self::settings($settings)];
      $files = [];
      $paths = [];
      foreach ($dirs as $key=>$dir) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', (string)$key)) throw new \RuntimeException('Kartnyckeln stöds inte i backup: ' . $key);
        $mapRoot = realpath($dir['path']);
        if ($mapRoot === false) throw new \RuntimeException('En konfigurerad kartkatalog saknas.');
        $paths[] = $mapRoot;
        $manifest['maps'][(string)$key] = ['label'=>(string)($dir['label'] ?? $key),'description'=>(string)($dir['description'] ?? '')];
        foreach ($this->inventory($mapRoot) as $name=>$path) $files['maps/' . $key . '/' . $name] = $path;
      }
      foreach ($this->inventory($this->root, $paths) as $name=>$path) $files['root/' . $name] = $path;
      $total = 0;
      foreach ($files as $name=>$path) {
        $size = filesize($path);
        $total += $size;
        if (count($files) > self::MAX_FILES || $size > self::MAX_FILE_BYTES || $total > self::MAX_BYTES) throw new \RuntimeException('Backupen överskrider gränsen: 20 000 filer, 25 MiB per fil eller 250 MiB totalt.');
        $hash = hash_file('sha256', $path);
        if ($hash === false || !$zip->addFile($path, $name)) throw new \RuntimeException('Kunde inte lägga till en fil i backupen.');
        $manifest['files'][$name] = ['size'=>$size,'sha256'=>$hash];
      }
      $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      if (strlen($json) > 8388608 || !$zip->addFromString('manifest.json', $json)) throw new \RuntimeException('Kunde inte skriva backupmanifest (max 8 MiB).');
    } catch (\Throwable $e) { $zip->close(); throw $e; }
    if (!$zip->close()) throw new \RuntimeException('Kunde inte färdigställa ZIP-filen.');
    if (filesize($zipPath) > 104857600) throw new \RuntimeException('ZIP-backupen överskrider importgränsen 100 MiB.');
    foreach ($files as $name=>$path) if (@hash_file('sha256', $path) !== $manifest['files'][$name]['sha256']) throw new \RuntimeException('Innehållet ändrades under exporten. Försök igen när inga redigeringar pågår.');
  }
  private static function settings(array $settings): array {
    if (array_diff(array_keys($settings), ['app','users','mcp'])) throw new \RuntimeException('Backupen innehåller okända admininställningar.');
    $app = $settings['app'] ?? [];
    if (!is_array($app) || array_diff(array_keys($app), ['site_name','default_interface','map_order','map_parents'])) throw new \RuntimeException('Ogiltiga webbplatsinställningar.');
    if (isset($app['site_name']) && (!is_string($app['site_name']) || trim($app['site_name']) === '' || mb_strlen($app['site_name']) > 100)) throw new \RuntimeException('Ogiltigt webbplatsnamn.');
    if (isset($app['default_interface']) && !in_array($app['default_interface'], ['classic','new'], true)) throw new \RuntimeException('Ogiltig standardvy.');
    foreach (['map_order','map_parents'] as $key) {
      if (!isset($app[$key])) continue;
      if (!is_array($app[$key])) throw new \RuntimeException('Ogiltig kartordning.');
      foreach ($app[$key] as $value) if (!is_string($value) && !is_int($value)) throw new \RuntimeException('Ogiltig kartordning.');
    }
    if (isset($settings['users'])) {
      if (!is_array($settings['users'])) throw new \RuntimeException('Ogiltiga användarinställningar.');
      foreach ($settings['users'] as $name=>$user) {
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', (string)$name)) throw new \RuntimeException('Ogiltigt användarnamn.');
        if ($user === null) continue;
        if (!is_array($user) || array_diff(array_keys($user), ['name','role','password_hash','session_version'])) throw new \RuntimeException('Ogiltiga kontoinställningar.');
        foreach ($user as $value) if (!is_string($value)) throw new \RuntimeException('Ogiltiga kontoinställningar.');
        if (isset($user['role']) && !in_array($user['role'], ['admin','editor'], true)) throw new \RuntimeException('Ogiltig användarroll.');
        if (isset($user['password_hash']) && password_get_info($user['password_hash'])['algoName'] === 'unknown') throw new \RuntimeException('Ogiltig lösenordshash.');
      }
    }
    if (isset($settings['mcp'])) {
      if (!is_array($settings['mcp']) || array_diff(array_keys($settings['mcp']), ['token_user','token_sha256'])) throw new \RuntimeException('Ogiltiga MCP-inställningar.');
      foreach ($settings['mcp'] as $value) if (!is_string($value)) throw new \RuntimeException('Ogiltiga MCP-inställningar.');
      $hash = $settings['mcp']['token_sha256'] ?? '';
      if ($hash !== '' && !preg_match('/^[a-f0-9]{64}$/D', $hash)) throw new \RuntimeException('Ogiltig MCP-nyckelhash.');
    }
    return $settings;
  }
  /** Validate all entries before writing; extract only manifest-listed data files. */
  public function restore(string $zipPath, string $project, string $existingFiles = 'keep', bool $restoreSettings = false, array $currentDirs = []): array {
    if (!in_array($existingFiles, ['keep','overwrite'], true)) throw new \RuntimeException('Välj hur befintliga filer ska hanteras.');
    $zip = self::zip();
    if ($zip->open($zipPath) !== true) throw new \RuntimeException('Filen är inte ett läsbart ZIP-arkiv.');
    $stage = $this->root . '/.capmap-import-' . bin2hex(random_bytes(8));
    $created = [];
    $changed = [];
    $published = [];
    $lock = null;
    $settingsLock = null;
    $activated = false;
    try {
      if ($zip->numFiles > self::MAX_FILES + 1) throw new \RuntimeException('ZIP-filen innehåller för många filer.');
      $stat = $zip->statName('manifest.json');
      if (!$stat || $stat['size'] > 8388608) throw new \RuntimeException('Backupmanifest saknas eller är för stort.');
      $manifest = json_decode($zip->getFromName('manifest.json'), true, 64, JSON_THROW_ON_ERROR);
      if (!is_array($manifest) || ($manifest['format'] ?? '') !== 'capability-map-backup' || ($manifest['version'] ?? 0) !== 1
          || !is_array($manifest['maps'] ?? null) || !$manifest['maps'] || !is_array($manifest['files'] ?? null) || !is_array($manifest['settings'] ?? null)) throw new \RuntimeException('ZIP-filen är inte en stödd systembackup.');
      $settings = self::settings($manifest['settings']);
      if (count($manifest['maps']) > 1000) throw new \RuntimeException('Backupen innehåller för många kartor (max 1000).');
      foreach ($manifest['maps'] as $key=>$map) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', (string)$key) || !is_array($map) || !is_string($map['label'] ?? null) || !is_string($map['description'] ?? null)) throw new \RuntimeException('Ogiltig kartkonfiguration.');
      }
      $total = 0; $seen = [];
      for ($i=0; $i<$zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat) throw new \RuntimeException('Kunde inte läsa ZIP-innehållet.');
        $name = $stat['name'];
        self::safeName($name);
        $folded = strtolower($name);
        if (isset($seen[$folded])) throw new \RuntimeException('ZIP-filen innehåller dubbla sökvägar.');
        $seen[$folded] = true;
        $zip->getExternalAttributesIndex($i, $opsys, $attributes);
        if ($opsys === 3 && (($attributes >> 16) & 0170000) !== 0 && (($attributes >> 16) & 0170000) !== 0100000) throw new \RuntimeException('Länkar och specialfiler stöds inte.');
        if ($name === 'manifest.json') continue;
        $record = $manifest['files'][$name] ?? null;
        if (!is_array($record) || !is_int($record['size'] ?? null) || !is_string($record['sha256'] ?? null) || !preg_match('/^[a-f0-9]{64}$/D', $record['sha256']) || $record['size'] !== $stat['size']) throw new \RuntimeException('Fil saknas i manifestet eller har fel storlek.');
        self::dataFile($name);
        $parts = explode('/', $name, 3);
        if (($parts[0] === 'maps' && (!isset($manifest['maps'][$parts[1] ?? '']) || empty($parts[2]))) || !in_array($parts[0], ['maps','root'], true) || ($parts[0] === 'root' && !isset($parts[1]))) throw new \RuntimeException('Okänd filsökväg i backupen.');
        foreach (explode('/', $name) as $part) if (self::transient($part) || in_array(strtolower($part), ['.capmap-admin.php','.capmap-config.json'], true)) throw new \RuntimeException('Reserverad fil i backupen.');
        $total += $stat['size'];
        if ($stat['size'] < 0 || $stat['size'] > self::MAX_FILE_BYTES || $total > self::MAX_BYTES) throw new \RuntimeException('ZIP-filen är för stor (max 250 MiB, 25 MiB per fil).');
      }
      if (count($manifest['files']) !== $zip->numFiles - 1) throw new \RuntimeException('Backupen är ofullständig.');
      if (!is_writable($this->root)) throw new \RuntimeException('Innehållsroten måste vara skrivbar.');
      $lock = fopen($this->root . '/.capmap-migration.lock', 'c');
      if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('En annan innehållsändring pågår.');
      if (!mkdir($stage, 0775)) throw new \RuntimeException('Kunde inte skapa importens arbetskatalog.');
      $created[] = $stage;
      $maps = $manifest['maps'];
      $preserved = 0;
      $existingIds = [];
      $copyCount = count($manifest['files']);
      $copyBytes = $total;
      if ($existingFiles === 'keep') {
        foreach ($currentDirs as $key=>$dir) {
          if (!preg_match('/^[a-zA-Z0-9_-]+$/D', (string)$key)) throw new \RuntimeException('Ogiltig befintlig kartnyckel.');
          $maps[$key] = ['label'=>(string)($dir['label'] ?? $key),'description'=>(string)($dir['description'] ?? '')];
          foreach ($this->inventory($dir['path']) as $name=>$source) {
            $copyBytes += filesize($source);
            if (++$copyCount > self::MAX_FILES || filesize($source) > self::MAX_FILE_BYTES || $copyBytes > self::MAX_BYTES) throw new \RuntimeException('Befintliga filer och backupen överskrider importens storleksgräns.');
            $path = $stage . '/maps/' . $key . '/' . $name;
            self::mkdirs(dirname($path), $stage, $created);
            $created[] = $path;
            if (!copy($source, $path) || hash_file('sha256', $source) !== hash_file('sha256', $path)) throw new \RuntimeException('Kunde inte bevara en befintlig fil.');
            if (preg_match('/\.(md|markdown)$/iD', $name)) {
              $meta = Frontmatter::parse((string)file_get_contents($path))['meta'];
              if (is_string($meta['id'] ?? null)) $existingIds[$key][$meta['id']] = $name;
            }
          }
        }
      }
      foreach ($manifest['files'] as $name=>$record) {
        $data = $zip->getFromName($name);
        if ($data === false || strlen($data) !== $record['size'] || !hash_equals($record['sha256'], hash('sha256', $data))) throw new \RuntimeException('Kontrollsumman stämmer inte: ' . $name);
        $path = $stage . '/' . $name;
        if ($existingFiles === 'keep' && str_starts_with($name, 'maps/')) {
          $parts = explode('/', $name, 3);
          $sameId = false;
          if (preg_match('/\.(md|markdown)$/iD', $name)) {
            $meta = Frontmatter::parse($data)['meta'];
            $sameId = is_string($meta['id'] ?? null) && isset($existingIds[$parts[1]][$meta['id']]);
          }
          if (file_exists($path) || $sameId) { $preserved++; continue; }
        }
        self::mkdirs(dirname($path), $stage, $created);
        $created[] = $path;
        if (file_put_contents($path, $data, LOCK_EX) !== strlen($data)) throw new \RuntimeException('Kunde inte skriva importfilen.');
      }
      if (count($maps) > 1000) throw new \RuntimeException('Importen skulle ge fler än 1000 kartor.');
      $config = ['content_root'=>realpath($project . '/content') === $this->root ? 'content' : $this->root, 'content_dirs'=>[]];
      foreach ($maps as $key=>$map) {
        $source = $stage . '/maps/' . $key;
        self::mkdirs($source, $stage, $created);
        $folder = 'capmap-restored-' . substr(basename($stage), -16) . '-' . $key;
        $target = $this->root . '/' . $folder;
        if (file_exists($target) || is_link($target) || !rename($source, $target)) throw new \RuntimeException('Kunde inte publicera den importerade kartan.');
        $published[] = [$source, $target];
        $config['content_dirs'][$key] = $map + ['folder'=>$folder];
      }
      // Root attachments and settings use reversible, per-file replacements.
      foreach ($manifest['files'] as $name=>$record) {
        if (!str_starts_with($name, 'root/')) continue;
        $dest = $this->destination(substr($name, 5), $created);
        if ($existingFiles === 'keep' && file_exists($dest)) { $preserved++; continue; }
        $this->replace($dest, (string)file_get_contents($stage . '/' . $name), $changed);
      }
      $adminPath = $project . '/content/.capmap-admin.php';
      $configPath = $project . '/content/.capmap-config.json';
      if (!is_dir(dirname($adminPath)) || !is_writable(dirname($adminPath))) throw new \RuntimeException('Appens katalog content måste vara skrivbar för inställningarna.');
      $settingsLock = fopen($adminPath . '.lock', 'c');
      if (!$settingsLock || !flock($settingsLock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('En annan ändring av admininställningarna pågår.');
      if ($restoreSettings) $this->replace($adminPath, "<?php\nreturn " . var_export($settings, true) . ";\n", $changed);
      $this->replace($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), $changed);
      $activated = true;
      foreach ($changed as $change) if ($change['backup'] !== null) @unlink($change['backup']);
      foreach (array_reverse($created) as $path) {
        if (is_dir($path)) @rmdir($path); elseif (is_file($path)) @unlink($path);
      }
      return ['maps'=>count($maps),'files'=>count($manifest['files']),'preserved_files'=>$preserved,'settings_restored'=>$restoreSettings];
    } catch (\Throwable $e) {
      $rollbackFailed = false;
      foreach (array_reverse($changed) as $change) {
        if (is_file($change['path']) && !@unlink($change['path'])) { $rollbackFailed = true; continue; }
        if ($change['backup'] !== null && !@rename($change['backup'], $change['path'])) $rollbackFailed = true;
        if (function_exists('opcache_invalidate')) opcache_invalidate($change['path'], true);
      }
      foreach (array_reverse($published) as [$source, $target]) if (!@rename($target, $source)) $rollbackFailed = true;
      if (!$rollbackFailed && !$activated) foreach (array_reverse($created) as $path) {
        if (is_dir($path)) @rmdir($path); elseif (is_file($path)) @unlink($path);
      }
      throw new \RuntimeException($e->getMessage() . ($rollbackFailed ? ' Återställningen kunde inte slutföras; kontrollera ' . $stage : ' Befintliga kartor har inte ersatts.'), 0, $e);
    } finally {
      $zip->close();
      if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
      if (is_resource($settingsLock)) { flock($settingsLock, LOCK_UN); fclose($settingsLock); }
    }
  }
  private static function mkdirs(string $path, string $stop, array &$created): void {
    if (is_dir($path)) return;
    if ($path === $stop || dirname($path) === $path) throw new \RuntimeException('Ogiltig målkatalog.');
    self::mkdirs(dirname($path), $stop, $created);
    if (!mkdir($path, 0775)) throw new \RuntimeException('Kunde inte skapa målkatalog.');
    $created[] = $path;
  }
  private function destination(string $relative, array &$created): string {
    $cursor = $this->root;
    foreach (explode('/', $relative) as $part) {
      $cursor .= DIRECTORY_SEPARATOR . $part;
      if (is_link($cursor)) throw new \RuntimeException('Målet innehåller en symbolisk länk.');
    }
    self::mkdirs(dirname($cursor), $this->root, $created);
    if (file_exists($cursor) && !is_file($cursor)) throw new \RuntimeException('Målfilen är en katalog.');
    return $cursor;
  }
  private function replace(string $path, string $data, array &$changed): void {
    if (is_link($path)) throw new \RuntimeException('Inställningsfilen får inte vara en symbolisk länk.');
    if (file_exists($path) && !is_file($path)) throw new \RuntimeException('Målfilen är inte en vanlig fil.');
    $backup = null;
    if (file_exists($path)) {
      $backup = $path . '.backup-' . bin2hex(random_bytes(8)) . (str_ends_with($path, '.php') ? '.php' : '.tmp');
      if (!rename($path, $backup)) throw new \RuntimeException('Kunde inte säkerhetskopiera målfilen.');
    }
    $changed[] = ['path'=>$path,'backup'=>$backup];
    $temp = $path . '.' . bin2hex(random_bytes(8)) . (str_ends_with($path, '.php') ? '.php' : '.tmp');
    try {
      if (file_put_contents($temp, $data, LOCK_EX) !== strlen($data) || !rename($temp, $path)) throw new \RuntimeException('Kunde inte aktivera importen.');
      if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);
    } finally { if (is_file($temp)) @unlink($temp); }
  }
}
