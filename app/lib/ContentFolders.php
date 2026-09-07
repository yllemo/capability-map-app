<?php
declare(strict_types=1);
namespace App;

final class ContentFolders {
  public static function manage(string $project, string $key, string $action, array $input): void {
    $root = realpath($project . '/content');
    if ($root === false) throw new \RuntimeException('Innehållsroten saknas.');
    $lock = @fopen($root . '/.capmap-migration.lock', 'c');
    if (!$lock) throw new \RuntimeException('Kunde inte låsa innehållskonfigurationen.');
    try {
      if (!flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('En annan katalogändring pågår.');
      $configPath = ContentMigration::configPath($project);
      if (!is_file($configPath)) throw new \RuntimeException('Migrera först kartorna till /content.');
      $config = json_decode((string)file_get_contents($configPath), true, 32, JSON_THROW_ON_ERROR);
      if (($config['content_root'] ?? '') !== 'content' || !isset($config['content_dirs'][$key])) throw new \RuntimeException('Kartan saknas i innehållskonfigurationen.');
      $entry = $config['content_dirs'][$key];
      $folder = $entry['folder'] ?? '';
      if (!is_string($folder) || !preg_match('/^[a-zA-Z0-9_-]+$/D', $folder)) throw new \RuntimeException('Ogiltig katalog i konfigurationen.');
      $path = $root . DIRECTORY_SEPARATOR . $folder;
      $resolved = realpath($path);
      if ($resolved === false || !is_dir($resolved) || is_link($path) || dirname($resolved) !== $root) throw new \RuntimeException('Katalogen måste ligga direkt under /content och får inte vara en symbolisk länk.');
      foreach ($config['content_dirs'] as $otherKey => $other) {
        if ((string)$otherKey !== $key && strcasecmp((string)($other['folder'] ?? ''), $folder) === 0) throw new \RuntimeException('Flera kartor använder samma katalog. Rätta konfigurationen först.');
      }
      if ($action === 'rename') {
        $label = trim((string)($input['label'] ?? ''));
        $newFolder = trim((string)($input['folder'] ?? ''));
        if ($label === '' || !preg_match('/^[a-zA-Z0-9_-]+$/D', $newFolder)) throw new \RuntimeException('Ange visningsnamn och ett katalognamn med bokstäver, siffror, - eller _.');
        $target = $root . DIRECTORY_SEPARATOR . $newFolder;
        $moved = $newFolder !== $folder;
        foreach ($config['content_dirs'] as $otherKey => $other) {
          if ((string)$otherKey !== $key && strcasecmp((string)($other['folder'] ?? ''), $newFolder) === 0) throw new \RuntimeException('Katalognamnet är redan konfigurerat för en annan karta.');
        }
        if ($moved && (file_exists($target) || is_link($target))) throw new \RuntimeException('Det nya katalognamnet används redan.');
        if ($moved && !@rename($path, $target)) throw new \RuntimeException('Katalogen kunde inte byta namn. Om den är en monterad volym kan du ändra enbart visningsnamnet.');
        $config['content_dirs'][$key]['label'] = $label;
        $config['content_dirs'][$key]['folder'] = $newFolder;
        try { ContentMigration::writeConfig($project, $config); }
        catch (\Throwable $e) {
          if ($moved && !@rename($target, $path)) throw new \RuntimeException('Konfigurationen kunde inte sparas och namnbytet kunde inte återställas. Katalogen ligger på ' . $target);
          throw $e;
        }
      } elseif ($action === 'delete') {
        if (count($config['content_dirs']) < 2) throw new \RuntimeException('Skapa en annan karta innan du tar bort den sista.');
        if (($input['confirmation'] ?? '') !== ($entry['label'] ?? $key)) throw new \RuntimeException('Skriv kartans visningsnamn exakt för att bekräfta radering.');
        // Snapshot only descendants of the checked root; never follow symlinks.
        $items = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resolved, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
          $candidate = $item->getPathname();
          $parent = realpath(dirname($candidate));
          if ($parent === false || ($parent !== $resolved && !str_starts_with($parent, $resolved . DIRECTORY_SEPARATOR))) throw new \RuntimeException('En sökväg ligger utanför kartkatalogen.');
          if (!is_writable($parent)) throw new \RuntimeException('Skrivrättighet saknas för ' . $parent);
          $items[] = [$candidate, $item->isDir() && !$item->isLink()];
        }
        // Check configuration can be written before deleting any content.
        ContentMigration::writeConfig($project, $config);
        foreach ($items as [$candidate, $directory]) {
          if (!($directory ? @rmdir($candidate) : @unlink($candidate))) throw new \RuntimeException('Raderingen avbröts vid ' . $candidate . '. Vissa filer kan ha raderats; kartan finns kvar i konfigurationen.');
        }
        unset($config['content_dirs'][$key]);
        try { ContentMigration::writeConfig($project, $config); }
        catch (\Throwable $e) { throw new \RuntimeException('Filerna är raderade, men den tomma kartan kunde inte tas bort ur konfigurationen. Försök igen.'); }
        // Removing a mount point can fail; the contents and config are already removed.
        @rmdir($resolved);
      } else throw new \RuntimeException('Okänd katalogåtgärd.');
    } finally { flock($lock, LOCK_UN); fclose($lock); }
  }

  public static function create(string $project, array $app, string $key, string $label, string $description): string {
    if (!preg_match('/^[a-z0-9_-]+$/D', $key) || trim($label) === '') {
      throw new \InvalidArgumentException('Ange en folder-nyckel med små bokstäver, siffror, - eller _ och ett visningsnamn.');
    }
    $root = realpath($project . '/content');
    if ($root === false || !is_writable($root)) throw new \RuntimeException('/content måste finnas och vara skrivbar för PHP.');
    $lock = @fopen($root . '/.capmap-migration.lock', 'c');
    if (!$lock) throw new \RuntimeException('Kunde inte öppna kataloglåset under /content. Kontrollera skrivrättigheterna.');
    $created = false;
    $target = $root . DIRECTORY_SEPARATOR . $key;
    try {
      if (!flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('En annan katalogändring pågår. Försök igen.');
      // Read after locking so another request's new map cannot be overwritten.
      $configPath = ContentMigration::configPath($project);
      if (is_file($configPath)) {
        $json = @file_get_contents($configPath);
        if ($json === false) throw new \RuntimeException('Kunde inte läsa innehållskonfigurationen.');
        $config = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
      } else {
        // Already migrated manually? Convert existing paths without moving files.
        $dirs = $app['content_dirs'] ?? ['content' => ['path' => $app['content_dir'] ?? $project . '/content', 'label' => 'Content']];
        $config = ['content_root' => 'content', 'content_dirs' => []];
        foreach ($dirs as $existingKey => $dir) {
          $path = realpath($dir['path'] ?? '');
          if ($path === false || !is_dir($path) || dirname($path) !== $root) {
            throw new \RuntimeException('Kör först Flytta innehåll till /content i editorn. Alla kartor behöver ligga i egna kataloger under /content.');
          }
          unset($dir['path']);
          $dir['folder'] = basename($path);
          $config['content_dirs'][$existingKey] = $dir;
        }
      }
      if (!is_array($config) || ($config['content_root'] ?? '') !== 'content' || !isset($config['content_dirs']) || !is_array($config['content_dirs'])) {
        throw new \RuntimeException('Innehållskonfigurationen måste ange content_root: content och en lista med kartkataloger.');
      }
      foreach ($config['content_dirs'] as $existingKey => $dir) {
        if (!is_array($dir) || !is_string($dir['folder'] ?? null)) throw new \RuntimeException('En kartpost saknar ett giltigt folder-fält.');
        if (strcasecmp((string)$existingKey, $key) === 0 || strcasecmp($dir['folder'], $key) === 0) {
          throw new \RuntimeException('Folder-nyckeln eller katalogen används redan av en karta.');
        }
      }
      if (file_exists($target) || is_link($target)) throw new \RuntimeException('Katalogen finns redan i filsystemet. Välj en annan nyckel.');
      if (!@mkdir($target, 0775)) throw new \RuntimeException('Kunde inte skapa katalogen under /content. Kontrollera skrivrättigheterna.');
      $created = true;
      $config['content_dirs'][$key] = ['folder' => $key, 'label' => $label, 'description' => $description];
      ContentMigration::writeConfig($project, $config);
      return $target;
    } catch (\Throwable $e) {
      if ($created && !@rmdir($target)) throw new \RuntimeException($e->getMessage() . ' Den nya katalogen kunde inte återställas: ' . $target);
      throw $e;
    } finally {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
  }
}
