<?php
declare(strict_types=1);

namespace App;

final class Capability {
  public string $id;
  public string $name;
  public string $layer;
  public string $area;
  public int $level;
  public string $type;
  public string $description;
  public array $meta;
  public string $path; // absolute path on disk

  public function __construct(array $meta, string $path) {
    $this->meta = $meta;
    $this->path = $path;
    $this->id = (string)($meta['id'] ?? '');
    $this->name = (string)($meta['name'] ?? $meta['title'] ?? '');
    $this->layer = (string)($meta['layer'] ?? '');
    $this->area = (string)($meta['area'] ?? '');
    $this->level = (int)($meta['level'] ?? 0);
    $this->type = (string)($meta['type'] ?? '');
    $this->description = (string)($meta['description'] ?? $meta['purpose'] ?? '');
  }

  public function get(string $key, mixed $default=null): mixed {
    return $this->meta[$key] ?? $default;
  }
}
