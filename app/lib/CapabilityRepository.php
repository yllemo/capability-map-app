<?php
declare(strict_types=1);

namespace App;

final class CapabilityRepository {
  private string $contentDir;
  private array $contentDirs;

  public function __construct(string $contentDir, ?array $contentDirs = null) {
    $this->contentDir = rtrim($contentDir, '/');
    $this->contentDirs = $contentDirs ?? (function_exists('get_content_dirs') ? \get_content_dirs() : []);
  }

  /** @return array<Capability> */
  public function all(): array {
    $caps = [];
    foreach ($this->iterateMarkdownFiles($this->contentDir) as $file) {
      $raw = file_get_contents($file);
      if ($raw === false) continue;
      $parsed = Frontmatter::parse($raw);
      $meta = $parsed['meta'] ?? [];
      if (!is_array($meta)) $meta = [];
      if (!isset($meta['id']) || (!isset($meta['name']) && !isset($meta['redirect_map']))) continue;
      $caps[] = $this->displayCapability($meta, $file);
    }
    usort($caps, fn($a,$b) => strcmp($a->name, $b->name));
    return $caps;
  }

  public function byId(string $id): ?array {
    $data = $this->rawById($id);
    if ($data) $data['cap'] = $this->displayCapability($data['cap']->meta, $data['cap']->path);
    return $data;
  }

  public function rawById(string $id): ?array {
    foreach ($this->iterateMarkdownFiles($this->contentDir) as $file) {
      $raw = file_get_contents($file);
      if ($raw === false) continue;
      $parsed = Frontmatter::parse($raw);
      $meta = $parsed['meta'] ?? [];
      if (is_array($meta) && (($meta['id'] ?? '') === $id)) {
        return ['cap' => new Capability($meta, $file), 'body' => $parsed['body'] ?? ''];
      }
    }
    return null;
  }

  private function displayCapability(array $meta, string $file): Capability {
    if (!isset($meta['redirect_map'])) return new Capability($meta, $file);
    try {
      $target = CapabilityReference::resolve($meta, $this->contentDirs);
      if (function_exists('can_read_map') && !\can_read_map($target['map'])) {
        throw new \RuntimeException('Du saknar behörighet att läsa originalförmågan.');
      }
      $display = $target['cap']->meta;
      foreach (['layer', 'maturity', 'criticality', 'risk_level', 'level'] as $key) {
        if (array_key_exists($key, $meta) && $meta[$key] !== '') $display[$key] = $meta[$key];
      }
      $display['id'] = $meta['id'];
      $display['redirect_map'] = $target['map'];
      $display['redirect_id'] = $target['cap']->id;
      return new Capability($display, $file);
    } catch (\RuntimeException $e) {
      return new Capability(array_merge(['layer' => 'verksamhetsstod', 'area' => 'Referenser'], $meta, ['name' => 'Bruten referens', 'description' => $e->getMessage()]), $file);
    }
  }

  /** @return array<string> */
  private function iterateMarkdownFiles(string $dir): array {
    $out = [];
    if (!is_dir($dir)) return $out;
    $entries = new \RecursiveCallbackFilterIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      fn($entry) => !($entry->isDir() && str_starts_with($entry->getFilename(), '.capmap-import-'))
    );
    $it = new \RecursiveIteratorIterator($entries);
    foreach ($it as $f) {
      /** @var \SplFileInfo $f */
      if (!$f->isFile()) continue;
      $ext = strtolower($f->getExtension());
      if ($ext !== 'md' && $ext !== 'markdown') continue;
      $out[] = $f->getPathname();
    }
    return $out;
  }
}
