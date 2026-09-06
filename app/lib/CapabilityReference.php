<?php
declare(strict_types=1);
namespace App;

final class CapabilityReference {
  /** Resolve only configured maps and IDs, never arbitrary paths or URLs. */
  public static function resolve(array $meta, array $dirs): array {
    $seen = [];
    for ($depth = 0; $depth < 32; $depth++) {
      $map = $meta['redirect_map'] ?? null;
      $id = $meta['redirect_id'] ?? null;
      if (!is_string($map) || !is_string($id) || $id === '' || !isset($dirs[$map])) throw new \RuntimeException('Referensens karta eller mål saknas.');
      $key = json_encode([$map, $id]);
      if (isset($seen[$key])) throw new \RuntimeException('Referensen innehåller en omstyrningsloop.');
      $seen[$key] = true;
      $data = (new CapabilityRepository($dirs[$map]['path'], $dirs))->rawById($id);
      if (!$data) throw new \RuntimeException('Originalförmågan finns inte längre i den valda kartan.');
      if (!isset($data['cap']->meta['redirect_map'])) return ['map' => $map, 'cap' => $data['cap'], 'body' => $data['body']];
      $meta = $data['cap']->meta;
    }
    throw new \RuntimeException('Referenskedjan är för lång.');
  }

  public static function markdown(string $id, string $selection, array $dirs): string {
    $choice = json_decode($selection, true);
    if (!is_array($choice) || count($choice) !== 2) throw new \RuntimeException('Välj en originalförmåga.');
    $target = self::resolve(['redirect_map' => $choice[0] ?? null, 'redirect_id' => $choice[1] ?? null], $dirs);
    $meta = ['id' => $id, 'redirect_map' => $target['map'], 'redirect_id' => $target['cap']->id];
    $text = "---\n";
    foreach ($meta as $key => $value) $text .= $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    return $text . "---\n";
  }

  public static function choices(array $dirs): array {
    $choices = [];
    foreach ($dirs as $map => $dir) {
      foreach ((new CapabilityRepository($dir['path'], $dirs))->all() as $cap) {
        if (isset($cap->meta['redirect_map'])) continue;
        $choices[] = ['map' => (string)$map, 'label' => $dir['label'] ?? $map, 'id' => $cap->id, 'name' => $cap->name];
      }
    }
    return $choices;
  }
}
