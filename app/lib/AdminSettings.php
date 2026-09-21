<?php
declare(strict_types=1);
namespace App;

final class AdminSettings {
  public static function path(): string { return __DIR__ . '/../../content/.capmap-admin.php'; }
  public static function read(): array {
    $path = self::path();
    return is_file($path) ? require $path : [];
  }
  public static function update(callable $change): void {
    $path = self::path();
    $lock = fopen($path . '.lock', 'c');
    if (!$lock) throw new \RuntimeException('Kan inte skriva inställningar under /content.');
    $temp = null;
    try {
      if (!flock($lock, LOCK_EX)) throw new \RuntimeException('Kan inte låsa inställningarna.');
      $data = $change(self::read());
      $text = "<?php\n// Managed by /admin. No output when requested directly.\nreturn " . var_export($data, true) . ";\n";
      $temp = $path . '.' . bin2hex(random_bytes(8)) . '.php';
      if (file_put_contents($temp, $text, LOCK_EX) !== strlen($text) || !rename($temp, $path)) throw new \RuntimeException('Kunde inte spara inställningarna.');
      if (function_exists('opcache_invalidate')) opcache_invalidate($path, true);
    } finally {
      if ($temp && is_file($temp)) unlink($temp);
      flock($lock, LOCK_UN); fclose($lock);
    }
  }
}
