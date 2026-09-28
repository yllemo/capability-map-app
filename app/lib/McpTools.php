<?php
declare(strict_types=1);
namespace App;

/** Read-only MCP tools. All map selection is explicit, never session-derived. */
final class McpTools {
  public function __construct(private array $dirs, private array $config, private bool $authenticated) {}

  public static function definitions(): array {
    $string = ['type'=>'string'];
    $paging = ['offset'=>['type'=>'integer','minimum'=>0], 'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>100]];
    $definitions = [
      ['maps_list', 'List readable capability maps and their stable keys.', [], []],
      ['capabilities_list', 'List or search capabilities by YAML ID, name, description or exact tag. Omit map to search all readable maps.', ['map'=>$string,'query'=>$string,'tag'=>$string] + $paging, []],
      ['capabilities_read', 'Read Markdown and metadata by YAML ID or relative Markdown file. Specify map; references include their resolved original when readable.', ['map'=>$string,'id'=>$string,'file'=>$string], ['map']],
      ['skills_list', 'List configured instruction documents (requires authentication).', $paging, []],
      ['skills_read', 'Read one configured instruction document by its listed ID (requires authentication).', ['id'=>$string], ['id']],
    ];
    return array_map(static fn($d) => ['name'=>$d[0], 'description'=>$d[1],
      'inputSchema'=>['type'=>'object','properties'=>(object)$d[2],'required'=>$d[3],'additionalProperties'=>false],
      'annotations'=>['readOnlyHint'=>true,'destructiveHint'=>false,'idempotentHint'=>true,'openWorldHint'=>false]], $definitions);
  }

  public function call(string $name, \stdClass $arguments): array {
    $definition = null;
    foreach (self::definitions() as $tool) if ($tool['name'] === $name) $definition = $tool;
    if (!$definition) throw new \InvalidArgumentException('Unknown tool: ' . $name);
    $args = (array)$arguments;
    $schema = $definition['inputSchema'];
    foreach ($schema['required'] as $key) if (!isset($args[$key]) || $args[$key] === '') throw new \InvalidArgumentException('Missing argument: ' . $key);
    foreach ($args as $key=>$value) {
      $rule = ((array)$schema['properties'])[$key] ?? null;
      if (!$rule || ($rule['type'] === 'string' ? !is_string($value) : !is_int($value))) throw new \InvalidArgumentException('Invalid argument: ' . $key);
      if (is_int($value) && ($value < ($rule['minimum'] ?? 0) || $value > ($rule['maximum'] ?? PHP_INT_MAX))) throw new \InvalidArgumentException('Argument out of range: ' . $key);
    }
    if ($name === 'maps_list') {
      $maps = [];
      foreach ($this->dirs as $key=>$dir) $maps[] = ['map'=>(string)$key,'name'=>$dir['label'] ?? (string)$key];
      return ['maps'=>$maps];
    }
    if (str_starts_with($name, 'skills_')) {
      if (!$this->authenticated) throw new \RuntimeException('Authentication required for instruction documents.');
      $skills = $this->skills();
      if ($name === 'skills_list') return $this->page(array_map(static fn($s)=>['id'=>$s['id'],'name'=>$s['name']], array_values($skills)), $args, 'skills');
      if (!isset($skills[$args['id']])) throw new \RuntimeException('Skill not found.');
      $skill = $skills[$args['id']];
      return ['id'=>$skill['id'],'name'=>$skill['name'],'content'=>$this->readFile($skill['path'])];
    }
    $map = $args['map'] ?? '';
    if ($map !== '' && !isset($this->dirs[$map])) throw new \RuntimeException('Map not found or access denied.');
    if ($name === 'capabilities_read') {
      if (empty($args['id']) && empty($args['file'])) throw new \InvalidArgumentException('Provide id or file.');
      $repo = new CapabilityRepository($this->dirs[$map]['path'], $this->dirs);
      foreach ($repo->all() as $cap) {
        $file = $this->relative($map, $cap->path);
        if (($args['id'] ?? $cap->id) !== $cap->id || ($args['file'] ?? $file) !== $file) continue;
        $markdown = $this->readFile($this->safeFile($map, $cap->path));
        $parsed = Frontmatter::parse($markdown);
        $result = ['map'=>$map,'file'=>$file,'id'=>$cap->id,'metadata'=>$parsed['meta'],'markdown'=>$markdown];
        if (isset($parsed['meta']['redirect_map'])) {
          try {
            $target = CapabilityReference::resolve($parsed['meta'], $this->dirs);
            $result['original'] = ['map'=>$target['map'],'id'=>$target['cap']->id,'markdown'=>$this->readFile($this->safeFile($target['map'], $target['cap']->path))];
          } catch (\RuntimeException $e) { $result['referenceError'] = 'Original not found or access denied.'; }
        }
        return $result;
      }
      throw new \RuntimeException('Capability not found.');
    }
    $items = [];
    foreach ($this->dirs as $key=>$dir) {
      if ($map !== '' && (string)$key !== $map) continue;
      foreach ((new CapabilityRepository($dir['path'], $this->dirs))->all() as $cap) {
        try { $this->safeFile((string)$key, $cap->path); } catch (\RuntimeException $e) { continue; }
        $tags = array_values(array_filter((array)$cap->get('tags', []), 'is_string'));
        if (($args['tag'] ?? '') !== '' && !in_array(mb_strtolower($args['tag']), array_map('mb_strtolower', $tags), true)) continue;
        $search = $cap->id . ' ' . $cap->name . ' ' . $cap->description . ' ' . implode(' ', $tags);
        if (($args['query'] ?? '') !== '' && !str_contains(mb_strtolower($search), mb_strtolower($args['query']))) continue;
        $items[] = ['map'=>(string)$key,'file'=>$this->relative((string)$key,$cap->path),'id'=>$cap->id,'name'=>$cap->name,'description'=>$cap->description,'layer'=>$cap->layer,'tags'=>$tags];
      }
    }
    usort($items, static fn($a,$b)=>strcmp($a['map'].'/'.$a['file'], $b['map'].'/'.$b['file']));
    return $this->page($items, $args, 'capabilities');
  }
  private function page(array $items, array $args, string $key): array {
    $offset = $args['offset'] ?? 0; $limit = $args['limit'] ?? 50;
    return [$key=>array_slice($items,$offset,$limit),'total'=>count($items),'nextOffset'=>$offset+$limit < count($items) ? $offset+$limit : null];
  }
  private function safeFile(string $map, string $path): string {
    $root = realpath($this->dirs[$map]['path']); $real = realpath($path);
    if (!$root || !$real || !str_starts_with($real, rtrim($root, '/\\') . DIRECTORY_SEPARATOR) || !is_file($real)) throw new \RuntimeException('File outside map.');
    return $real;
  }
  private function relative(string $map, string $path): string {
    return str_replace('\\','/', substr($path, strlen(rtrim($this->dirs[$map]['path'], '/\\')) + 1));
  }
  private function readFile(string $path): string {
    if (filesize($path) > 5 * 1024 * 1024) throw new \RuntimeException('Document exceeds 5 MB.');
    $text = file_get_contents($path);
    if ($text === false) throw new \RuntimeException('Could not read document.');
    return $text;
  }
  private function skills(): array {
    $paths = (array)($this->config['skills_paths'] ?? []);
    foreach ((array)($this->config['skills_directories'] ?? []) as $dir) {
      if (!is_dir($dir)) continue;
      foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && !$file->isLink() && preg_match('/\.(md|txt|json)$/i',$file->getFilename())) $paths[] = $file->getPathname();
      }
    }
    $result = [];
    foreach ($paths as $path) {
      $real = realpath($path);
      if ($real && is_file($real)) $result[sha1($real)] = ['id'=>sha1($real),'name'=>basename($real),'path'=>$real];
    }
    ksort($result); return $result;
  }
}
