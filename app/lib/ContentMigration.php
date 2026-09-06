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
  public function plan(array $dirs): array {
    if (!$dirs) throw new \RuntimeException('Inga innehållskataloger är konfigurerade.');
    $plan = [];
    $seen = [];
    foreach ($dirs as $key => $dir) {
      $source = realpath($dir['path']);
      if ($source === false || !is_dir($source) || is_link($dir['path'])) throw new \RuntimeException("Katalogen för $key saknas eller är en symbolisk länk.");
      $source = str_replace('\\', '/', $source);
      $folder = basename($source);
      if (dirname($source) !== $this->project || !preg_match('/^[a-zA-Z0-9_-]+$/', $folder)) throw new \RuntimeException("$key måste vara en vanlig katalog direkt under applikationens rot.");
      if (in_array(strtolower($folder), ['app', 'assets', 'config', 'editor', 'view', 'ai', 'mcp', 'storage', 'html', 'tests', 'vendor'], true)) throw new \RuntimeException("$folder är en applikationskatalog och får inte flyttas.");
      if (isset($seen[strtolower($source)])) throw new \RuntimeException('Flera kartor pekar på samma katalog. Rätta konfigurationen först.');
      $seen[strtolower($source)] = true;
      if (!is_writable($source)) throw new \RuntimeException("Katalogen $folder är inte skrivbar.");
      $plan[$key] = ['source' => $source, 'target' => $this->project . '/content/' . $folder, 'folder' => $folder, 'label' => $dir['label'] ?? $key, 'description' => $dir['description'] ?? ''];
    }
    $root = $this->project . '/content';
    if (file_exists($root) && !isset($seen[strtolower($root)])) throw new \RuntimeException('/content finns redan men ingår inte som källkatalog i konfigurationen. Flytten stoppas för att skydda innehållet.');
    if (!is_writable($this->project) || !is_writable($this->project . '/config') || !is_writable($this->project . '/storage')) throw new \RuntimeException('PHP behöver skrivrättighet till appens rot, config och storage.');
    return $plan;
  }
  public function run(array $dirs): void {
    $lock = fopen($this->project . '/storage/content-migration.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('En migrering pågår redan.');
    $moves = [];
    $rootCreated = false;
    $root = $this->project . '/content';
    $stage = $this->project . '/storage/content-migration-' . bin2hex(random_bytes(8));
    try {
      if (file_exists($this->project . '/config/content.local.json')) throw new \RuntimeException('Migreringen är redan genomförd.');
      $plan = $this->plan($dirs);
      if (!mkdir($stage, 0700)) throw new \RuntimeException('Kunde inte skapa tillfällig katalog.');
      // Persistent journal remains in storage for manual recovery after a process interruption.
      if (file_put_contents($stage . '/plan.json', json_encode($plan, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) throw new \RuntimeException('Kunde inte skriva flyttjournal.');
      foreach ($plan as $entry) {
        $temp = $stage . '/' . $entry['folder'];
        if (!rename($entry['source'], $temp)) throw new \RuntimeException('Kunde inte flytta ' . $entry['source']);
        $moves[] = [$entry['source'], $temp];
      }
      if (!mkdir($root, 0775)) throw new \RuntimeException('Kunde inte skapa den nya innehållsroten.');
      $rootCreated = true;
      foreach ($plan as $entry) {
        $temp = $stage . '/' . $entry['folder'];
        if (file_exists($entry['target']) || !rename($temp, $entry['target'])) throw new \RuntimeException('Kunde inte färdigställa ' . $entry['target']);
        $moves[] = [$temp, $entry['target']];
      }
      $newDirs = [];
      foreach ($plan as $key => $entry) $newDirs[$key] = ['folder' => $entry['folder'], 'label' => $entry['label'], 'description' => $entry['description']];
      self::writeConfig($this->project, ['content_root' => 'content', 'content_dirs' => $newDirs]);
    } catch (\Throwable $e) {
      $rollbackFailed = false;
      foreach (array_reverse($moves) as [$from, $to]) {
        if ($rootCreated && $from === $root && is_dir($root) && count(scandir($root)) === 2) { rmdir($root); $rootCreated = false; }
        if (!@rename($to, $from)) $rollbackFailed = true;
      }
      if ($rootCreated && is_dir($root) && count(scandir($root)) === 2) @rmdir($root);
      throw new \RuntimeException($e->getMessage() . ($rollbackFailed ? ' Återställningen blev ofullständig. Använd flyttjournalen i ' . $stage : ' Eventuella flyttar har återställts.'));
    } finally {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
  }
  public static function writeConfig(string $project, array $config): void {
    $path = $project . '/config/content.local.json';
    $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($temp, $json, LOCK_EX) !== strlen($json)) { @unlink($temp); throw new \RuntimeException('Kunde inte skriva innehållskonfigurationen.'); }
    if (!rename($temp, $path)) { @unlink($temp); throw new \RuntimeException('Kunde inte aktivera innehållskonfigurationen.'); }
  }
}
