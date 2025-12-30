<?php
declare(strict_types=1);

namespace App;

class Logger {
  private static function logFile(): string {
    $storageDir = cfg('app')['storage_dir'] ?? __DIR__ . '/../../storage';
    if (!is_dir($storageDir)) {
      @mkdir($storageDir, 0775, true);
    }
    return $storageDir . '/error.log';
  }

  public static function error(string $message, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logLine = "[{$timestamp}] ERROR: {$message}{$contextStr}\n";

    @file_put_contents(self::logFile(), $logLine, FILE_APPEND | LOCK_EX);
  }

  public static function info(string $message, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logLine = "[{$timestamp}] INFO: {$message}{$contextStr}\n";

    @file_put_contents(self::logFile(), $logLine, FILE_APPEND | LOCK_EX);
  }

  public static function audit(string $action, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $user = $_COOKIE[cfg('auth')['cookie_name'] ?? 'capmap_editor'] ?? 'anonymous';
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logLine = "[{$timestamp}] AUDIT: {$action} (user: {$user}){$contextStr}\n";

    @file_put_contents(self::logFile(), $logLine, FILE_APPEND | LOCK_EX);
  }
}
