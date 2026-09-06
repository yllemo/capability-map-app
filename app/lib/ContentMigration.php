<?php
declare(strict_types=1);
namespace App;

final class ContentMigration {
  private string $project;
  public function __construct(string $project) {
    $resolved = realpath($project);
    if ($resolved === false) throw new \RuntimeException('Applikationskatalogen saknas.');
    $this->project = str_replace('\\', '/', $resolved);
  }
  public static function configPath(string $project): string {
    $path = $project . '/content/.capmap-config.json';
    return is_file($path) ? $path : $project . '/config/content.local.json';
  }
  public function plan(array $dirs): array {
    $root = $this->project . '/content';
    if (!is_dir($root) || !is_writable($root)) throw new \RuntimeException('/content måste finnas och vara skrivbar för PHP. Ingen skrivrättighet till appens rot krävs.');
    if (!$dirs) throw new \RuntimeException('Inga innehållskataloger är konfigurerade.');
    $plan = [];
    $seen = [];
    foreach ($dirs as $key => $dir) {
      $source = realpath($dir['path']);
      if ($source === false || !is_dir($source) || !is_readable($source)) throw new \RuntimeException("Katalogen för $key saknas eller kan inte läsas.");
      $source = str_replace('\\', '/', $source);
      $folder = basename($source);
      if (dirname($source) !== $this->project || !preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) throw new \RuntimeException("$key måste vara en katalog direkt under applikationens rot.");
      if (in_array(strtolower($folder), ['app', 'assets', 'config', 'editor', 'view', 'ai', 'mcp', 'storage', 'html', 'tests', 'vendor'], true)) throw new \RuntimeException("$folder är en applikationskatalog och får inte migreras.");
      if (isset($seen[strtolower($source)])) throw new \RuntimeException('Flera kartor pekar på samma katalog. Rätta konfigurationen först.');
      $seen[strtolower($source)] = true;
      $target = $root . '/' . $folder;
      if (file_exists($target) || is_link($target)) throw new \RuntimeException("Målet $target finns redan. Ingen katalog skrivs över.");
      $plan[$key] = ['source' => $source, 'target' => $target, 'folder' => $folder, 'label' => $dir['label'] ?? $key, 'description' => $dir['description'] ?? ''];
    }
    if (file_exists($root . '/.capmap-migration')) throw new \RuntimeException('En tidigare kopiering har avbrutits. Kontrollera /content/.capmap-migration/plan.json och ta bort endast dess ofullständiga kopior innan du försöker igen. Originalen är kvar.');
    return $plan;
  }
  /** Snapshot sources before creating any destination, especially content/content. */
  private function inventory(string $source): array {
    $items = [];
    $walk = function(string $path, string $relative) use (&$walk, &$items): void {
      $names = scandir($path);
      if ($names === false) throw new \RuntimeException('Kunde inte läsa ' . $path);
      foreach ($names as $name) {
        if ($name === '.' || $name === '..' || ($relative === '' && str_starts_with($name, '.capmap-'))) continue;
        $abs = $path . '/' . $name;
        $rel = $relative === '' ? $name : $relative . '/' . $name;
        if (is_link($abs)) throw new \RuntimeException('Symboliska länkar stöds inte i kartinnehållet: ' . $abs);
        if (!is_readable($abs)) throw new \RuntimeException('Kunde inte läsa ' . $abs);
        if (is_dir($abs)) { $items[] = ['path' => $rel, 'directory' => true]; $walk($abs, $rel); }
        elseif (is_file($abs)) $items[] = ['path' => $rel, 'directory' => false];
        else throw new \RuntimeException('Filtypen stöds inte: ' . $abs);
      }
    };
    $walk($source, '');
    return $items;
  }
  public function run(array $dirs): array {
    $root = $this->project . '/content';
    $lock = fopen($root . '/.capmap-migration.lock', 'c');
    if (!$lock) throw new \RuntimeException('Kunde inte skapa låsfil under /content.');
    if (!flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); throw new \RuntimeException('En migrering pågår redan.'); }
    $created = [];
    $stage = $root . '/.capmap-migration';
    $published = [];
    $activated = false;
    $warnings = [];
    try {
      if (is_file(self::configPath($this->project))) throw new \RuntimeException('Migreringen är redan genomförd.');
      $plan = $this->plan($dirs);
      $inventories = [];
      foreach ($plan as $key => $entry) $inventories[$key] = $this->inventory($entry['source']);
      if (!mkdir($stage, 0775)) throw new \RuntimeException('Kunde inte skapa arbetskatalog under /content.');
      $created[] = $stage;
      $created[] = $stage . '/plan.json';
      if (file_put_contents($stage . '/plan.json', json_encode($plan, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) throw new \RuntimeException('Kunde inte skriva kopieringsjournal.');
      foreach ($plan as $key => $entry) {
        $temp = $stage . '/' . $entry['folder'];
        if (!mkdir($temp, 0775)) throw new \RuntimeException('Kunde inte skapa kopian.');
        $created[] = $temp;
        foreach ($inventories[$key] as $item) {
          $dest = $temp . '/' . $item['path'];
          $src = $entry['source'] . '/' . $item['path'];
          $created[] = $dest;
          if ($item['directory']) {
            if (!mkdir($dest, 0775)) throw new \RuntimeException('Kunde inte skapa ' . $dest);
          } else {
            if (!copy($src, $dest)) throw new \RuntimeException('Kunde inte kopiera ' . $src);
            $hash = hash_file('sha256', $src);
            if ($hash === false || $hash !== hash_file('sha256', $dest)) throw new \RuntimeException('Kontrollsumman stämmer inte för ' . $src);
          }
        }
      }
      foreach ($plan as $entry) {
        $temp = $stage . '/' . $entry['folder'];
        if (file_exists($entry['target']) || !rename($temp, $entry['target'])) throw new \RuntimeException('Kunde inte aktivera ' . $entry['target']);
        $published[] = [$temp, $entry['target']];
      }
      $newDirs = [];
      foreach ($plan as $key => $entry) $newDirs[$key] = ['folder' => $entry['folder'], 'label' => $entry['label'], 'description' => $entry['description']];
      self::writeConfig($this->project, ['content_root' => 'content', 'content_dirs' => $newDirs]);
      $activated = true;
      // Only remove inventoried source entries after the new configuration is
      // active. Never rename or remove a source root: it may be a PVC mount.
      foreach ($plan as $key => $entry) {
        foreach (array_reverse($inventories[$key]) as $item) {
          $src = $entry['source'] . '/' . $item['path'];
          $dest = $entry['target'] . '/' . $item['path'];
          if ($item['directory']) {
            if (!@rmdir($src)) $warnings[] = 'Källans undermapp lämnades kvar: ' . $src;
          } else {
            // Changed files or newly introduced symlinks must never be deleted.
            $hash = is_link($src) ? false : @hash_file('sha256', $src);
            if ($hash === false || $hash !== @hash_file('sha256', $dest)) {
              $warnings[] = 'Källfilen ändrades eller kunde inte verifieras och lämnades kvar: ' . $src;
            } elseif (!@unlink($src)) {
              $warnings[] = 'Källfilen kunde inte tas bort och lämnades kvar: ' . $src;
            }
          }
        }
      }
    } catch (\Throwable $e) {
      if ($activated) throw new \RuntimeException('De nya kartorna är aktiverade, men städningen av originalen avbröts. De nya kopiorna är kvar. ' . $e->getMessage());
      $rollbackFailed = false;
      foreach (array_reverse($published) as [$temp, $target]) if (!@rename($target, $temp)) $rollbackFailed = true;
      // Delete only copies created by this invocation; never recurse into sources.
      if (!$rollbackFailed) foreach (array_reverse($created) as $path) {
        if (is_dir($path)) { if (!@rmdir($path)) $rollbackFailed = true; }
        elseif (file_exists($path) && !@unlink($path)) $rollbackFailed = true;
      }
      throw new \RuntimeException($e->getMessage() . ($rollbackFailed ? ' Ofullständiga kopior finns kvar under /content och behöver kontrolleras.' : ' Originalen är oförändrade.'));
    } finally {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
    // The journal is retained, but only the configured map folders are now scanned.
    return $warnings;
  }
  public static function writeConfig(string $project, array $config): void {
    $path = $project . '/content/.capmap-config.json';
    $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($temp, $json, LOCK_EX) !== strlen($json)) { @unlink($temp); throw new \RuntimeException('Kunde inte skriva innehållskonfigurationen under /content.'); }
    if (!rename($temp, $path)) { @unlink($temp); throw new \RuntimeException('Kunde inte aktivera innehållskonfigurationen.'); }
  }
}
