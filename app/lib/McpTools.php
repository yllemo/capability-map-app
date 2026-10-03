<?php
declare(strict_types=1);
namespace App;

/** All map selection is explicit; writes require a validated API key and map ACL. */
final class McpTools {
  public function __construct(private array $dirs, private array $config, private bool $authenticated, private bool $hasApiKey = false, private array $editableDirs = []) {}

  public static function definitions(): array {
    $string = ['type'=>'string'];
    $paging = ['offset'=>['type'=>'integer','minimum'=>0], 'limit'=>['type'=>'integer','minimum'=>1,'maximum'=>100]];
    $definitions = [
      ['maps_list', 'List readable capability maps and their stable keys.', [], []],
      ['capabilities_list', 'List or search capabilities by YAML ID, name, description or exact tag. Omit map to search all readable maps.', ['map'=>$string,'query'=>$string,'tag'=>$string] + $paging, []],
      ['capabilities_read', 'Read Markdown and metadata by YAML ID or relative Markdown file. Specify map; references include their resolved original when readable.', ['map'=>$string,'id'=>$string,'file'=>$string], ['map']],
      ['capabilities_update', 'Replace an existing original capability Markdown document. Requires a Bearer API key and map edit access. Preserve its YAML ID. Pass expected_sha256 from capabilities_read to prevent overwriting concurrent changes. Linked cards must be updated via their original.', ['map'=>$string,'id'=>$string,'markdown'=>$string,'expected_sha256'=>$string], ['map','id','markdown','expected_sha256']],
      ['skills_list', 'List configured instruction documents (requires authentication).', $paging, []],
      ['skills_read', 'Read one configured instruction document by its listed ID (requires authentication).', ['id'=>$string], ['id']],
    ];
    return array_map(static fn($d) => ['name'=>$d[0], 'description'=>$d[1],
      'inputSchema'=>['type'=>'object','properties'=>(object)$d[2],'required'=>$d[3],'additionalProperties'=>false],
      'annotations'=>['readOnlyHint'=>$d[0] !== 'capabilities_update','destructiveHint'=>$d[0] === 'capabilities_update','idempotentHint'=>true,'openWorldHint'=>false]], $definitions);
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
    if ($name === 'capabilities_update') return $this->updateCapability($map, $args);
    if ($name === 'capabilities_read') {
      if (empty($args['id']) && empty($args['file'])) throw new \InvalidArgumentException('Provide id or file.');
      $repo = new CapabilityRepository($this->dirs[$map]['path'], $this->dirs);
      foreach ($repo->all() as $cap) {
        $file = $this->relative($map, $cap->path);
        if (($args['id'] ?? $cap->id) !== $cap->id || ($args['file'] ?? $file) !== $file) continue;
        $markdown = $this->readFile($this->safeFile($map, $cap->path));
        $parsed = Frontmatter::parse($markdown);
        $result = ['map'=>$map,'file'=>$file,'id'=>$cap->id,'metadata'=>$parsed['meta'],'markdown'=>$markdown,'sha256'=>hash('sha256', $markdown)];
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
  private function updateCapability(string $map, array $args): array {
    if (!$this->hasApiKey) throw new \RuntimeException('A valid Bearer API key is required for updates. Create one in Admin → MCP.');
    if (!isset($this->editableDirs[$map])) throw new \RuntimeException('Map edit access denied.');
    $markdown = $args['markdown'];
    if (strlen($markdown) > 5 * 1024 * 1024 || !mb_check_encoding($markdown, 'UTF-8')) throw new \InvalidArgumentException('Markdown must be UTF-8 and at most 5 MB.');
    if (!preg_match('/^[a-f0-9]{64}$/D', $args['expected_sha256'])) throw new \InvalidArgumentException('Invalid expected_sha256.');
    $meta = Frontmatter::parse($markdown)['meta'];
    if (($meta['id'] ?? null) !== $args['id'] || !is_string($meta['name'] ?? null) || trim($meta['name']) === '' || isset($meta['redirect_map'])) throw new \InvalidArgumentException('Preserve the original ID and provide a name; reference documents cannot be written.');
    $record = (new CapabilityRepository($this->dirs[$map]['path'], $this->dirs))->rawById($args['id']);
    if (!$record) throw new \RuntimeException('Capability not found.');
    if (isset($record['cap']->meta['redirect_map'])) throw new \RuntimeException('Update the original capability instead of its linked card.');
    $path = $this->safeFile($map, $record['cap']->path);
    $lock = fopen($path . '.mcp.lock', 'c');
    if (!$lock) throw new \RuntimeException('Could not lock document.');
    $temp = null;
    try {
      if (!flock($lock, LOCK_EX)) throw new \RuntimeException('Could not lock document.');
      if (!hash_equals($args['expected_sha256'], hash('sha256', $this->readFile($path)))) throw new \RuntimeException('Document changed. Read it again before updating.');
      $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
      if (file_put_contents($temp, $markdown, LOCK_EX) !== strlen($markdown) || !rename($temp, $path)) throw new \RuntimeException('Could not save document.');
      if (class_exists(Logger::class)) Logger::audit('capability_mcp_saved', ['map'=>$map,'id'=>$args['id'],'file'=>$this->relative($map, $path)]);
      return ['map'=>$map,'id'=>$args['id'],'file'=>$this->relative($map, $path),'sha256'=>hash('sha256', $markdown),'updated'=>true];
    } finally {
      if ($temp && is_file($temp)) unlink($temp);
      flock($lock, LOCK_UN); fclose($lock);
    }
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
