<?php
declare(strict_types=1);
namespace App;

final class ContentFolders {
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
