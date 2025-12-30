<?php
declare(strict_types=1);

namespace App;

final class CapabilityRepository {
  private string $contentDir;

  public function __construct(string $contentDir) {
    $this->contentDir = rtrim($contentDir, '/');
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
      if (!isset($meta['id']) || !isset($meta['name'])) continue;
      $caps[] = new Capability($meta, $file);
    }
    usort($caps, fn($a,$b) => strcmp($a->name, $b->name));
    return $caps;
  }

  public function byId(string $id): ?array {
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

  /** @return array<string> */
  private function iterateMarkdownFiles(string $dir): array {
    $out = [];
    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
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
