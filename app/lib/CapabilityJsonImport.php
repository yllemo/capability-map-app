<?php
declare(strict_types=1);

namespace App;

final class CapabilityJsonImport {
  public static function convert(string $json, string $idPrefix = ''): array {
    $idPrefix = trim($idPrefix);
    if ($idPrefix !== '') {
      if (!preg_match('/^[a-z][a-z0-9-]{0,79}$/D', $idPrefix)) throw new \InvalidArgumentException('ID-prefix får innehålla små bokstäver, siffror och bindestreck, börja med en bokstav och vara högst 80 tecken.');
      $idPrefix = rtrim($idPrefix, '-') . '-';
    }
    try {
      $data = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true, 64, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
      throw new \InvalidArgumentException('Filen innehåller ogiltig JSON.');
    }
    if (!is_array($data) || !isset($data['capabilities']) || !is_array($data['capabilities']) || !$data['capabilities'] || array_keys($data['capabilities']) !== range(0, count($data['capabilities']) - 1)) {
      throw new \InvalidArgumentException('capabilities måste vara en lista med minst en förmåga.');
    }
    if (count($data['capabilities']) > 2000) throw new \InvalidArgumentException('Högst 2 000 förmågor kan importeras åt gången.');
    foreach (['organization', 'description', 'version', 'exportDate'] as $key) {
      if (isset($data[$key]) && !is_string($data[$key])) throw new \InvalidArgumentException("Fältet $key måste vara text.");
    }
    $layers = ['strategic' => 'ledning_styrning', 'core' => 'karnprocesser', 'support' => 'verksamhetsstod'];
    $maturities = ['initial' => 1, 'developing' => 2, 'defined' => 3, 'managed' => 4, 'optimized' => 5];
    $batch = substr(hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), 0, 16);
    $files = [];
    $ids = [];
    foreach ($data['capabilities'] as $index => $item) {
      $row = $index + 1;
      if (!is_array($item) || !isset($item['id']) || !is_int($item['id']) || $item['id'] < 1) throw new \InvalidArgumentException("Förmåga $row: id måste vara ett positivt heltal.");
      if (isset($ids[$item['id']])) throw new \InvalidArgumentException("Förmåga $row: dubblerat id.");
      $ids[$item['id']] = true;
      foreach (['title', 'description', 'layer', 'status'] as $key) {
        if (!isset($item[$key]) || !is_string($item[$key]) || trim($item[$key]) === '') throw new \InvalidArgumentException("Förmåga $row: $key saknas eller är inte text.");
      }
      if (!isset($layers[$item['layer']]) || !isset($maturities[$item['status']])) throw new \InvalidArgumentException("Förmåga $row: okänt skikt eller mognadsstatus.");
      $url = $item['url'] ?? '';
      if (!is_string($url)) throw new \InvalidArgumentException("Förmåga $row: url måste vara text.");
      $url = trim($url);
      if ($url !== '') {
        if (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $url)) $url = 'https://' . $url;
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) throw new \InvalidArgumentException("Förmåga $row: länken måste vara en giltig HTTP- eller HTTPS-adress.");
      }
      $id = $idPrefix !== '' ? $idPrefix . $row : 'cap-json' . $batch . '-' . $item['id'];
      $meta = [
        'id' => $id, 'name' => $item['title'], 'layer' => $layers[$item['layer']],
        'area' => 'Importerade förmågor', 'level' => 1, 'type' => 'verksamhetsformaga',
        'description' => $item['description'], 'maturity' => $maturities[$item['status']],
        'source_id' => $item['id'], 'source_status' => $item['status'],
      ];
      if ($url !== '') $meta['url'] = $url;
      $markdown = "---\n";
      foreach ($meta as $key => $value) {
        if (is_string($value)) $value = preg_replace('/\R/u', ' ', $value);
        $markdown .= $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
      }
      $markdown .= "---\n\n# " . preg_replace('/\R/u', ' ', $item['title']) . "\n\n" . $item['description'] . "\n";
      if ($url !== '') $markdown .= "\n## Länk\n\n[Läs mer](" . str_replace(['(', ')'], ['%28', '%29'], $url) . ")\n";
      $files[$id . '.md'] = $markdown;
    }
    $importKey = $idPrefix === '' ? $batch : substr(hash('sha256', $batch . ':' . $idPrefix), 0, 16);
    return ['directory' => 'json-import-' . $importKey, 'files' => $files, 'source' => $data];
  }

  public static function save(array $import, string $contentDir): void {
    $target = rtrim($contentDir, '/\\') . DIRECTORY_SEPARATOR . $import['directory'];
    if (file_exists($target)) throw new \RuntimeException('Den här JSON-kartan har redan importerats. Inga filer ändrades.');
    $existing = (new CapabilityRepository($contentDir))->all();
    foreach ($existing as $cap) {
      if (isset($import['files'][$cap->id . '.md'])) throw new \RuntimeException('ID ' . $cap->id . ' finns redan i målkartan. Välj ett annat prefix. Inga filer ändrades.');
    }
    // Stage next to the destination: storage/ may be on a different PVC/device.
    // Repository readers ignore this reserved directory until publication.
    $stage = rtrim($contentDir, '/\\') . DIRECTORY_SEPARATOR . '.capmap-import-' . bin2hex(random_bytes(8));
    if (!@mkdir($stage, 0775)) throw new \RuntimeException('Kunde inte skapa importmappen i målkartan. Kontrollera skrivrättigheter.');
    $created = [];
    try {
      $files = $import['files'];
      $files['source.json'] = json_encode($import['source'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      foreach ($files as $name => $text) {
        $path = $stage . DIRECTORY_SEPARATOR . $name . '.tmp';
        $created[] = $path;
        if (@file_put_contents($path, $text, LOCK_EX) !== strlen($text)) throw new \RuntimeException('Kunde inte skriva importen.');
      }
      // All contents have been written successfully at this point.
      foreach ($created as $i => $path) {
        $final = substr($path, 0, -4);
        if (!@rename($path, $final)) throw new \RuntimeException('Kunde inte färdigställa importen.');
        $created[$i] = $final;
      }
      if (file_exists($target) || !@rename($stage, $target)) throw new \RuntimeException('Kunde inte färdigställa importen; kartan kan redan ha importerats.');
    } catch (\Throwable $e) {
      foreach ($created as $path) if (is_file($path)) @unlink($path);
      @rmdir($stage);
      throw $e;
    }
  }
}
