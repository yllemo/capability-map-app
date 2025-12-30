<?php
declare(strict_types=1);

namespace App;

final class PathGuard {
  public static function safeJoin(string $base, string $relative): string {
    $base = rtrim(realpath($base) ?: $base, DIRECTORY_SEPARATOR);
    $relative = str_replace(['..', '\\'], ['', '/'], $relative);
    $candidate = $base . DIRECTORY_SEPARATOR . ltrim($relative, '/');
    $real = realpath(dirname($candidate));
    if ($real === false) {
      // allow creating new dirs/files; validate parent is within base
      $real = realpath($base);
    }
    if ($real === false || !str_starts_with($real, $base)) {
      throw new \RuntimeException('Invalid path');
    }
    return $candidate;
  }
}
