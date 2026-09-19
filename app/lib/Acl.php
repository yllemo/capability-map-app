<?php
declare(strict_types=1);

namespace App;

/**
 * Per-map read/edit access, configured in config/acl.php.
 *
 * A rule is one of:
 *   '*' or true       – anyone (including a logged-out visitor)
 *   'authenticated'    – any logged-in user
 *   ['alice','bob']    – only these usernames
 *   false or []        – nobody
 */
final class Acl {
  /** @return array{default?:array,maps?:array} */
  private static function config(): array {
    $cfg = \cfg('acl');
    return is_array($cfg) ? $cfg : [];
  }

  /** @return list<string> */
  private static function subjects(?string $user): array {
    return $user === null ? ['*', 'guest'] : ['*', 'authenticated', $user];
  }

  private static function matches(mixed $rule, array $subjects): bool {
    if ($rule === true) return true;
    if ($rule === false || $rule === null) return false;
    if (is_string($rule)) $rule = [$rule];
    if (!is_array($rule)) return false;
    foreach ($rule as $entry) {
      if (is_string($entry) && in_array($entry, $subjects, true)) return true;
    }
    return false;
  }

  private static function rule(string $mapKey, string $action): mixed {
    $cfg = self::config();
    $mapRule = $cfg['maps'][$mapKey][$action] ?? null;
    if ($mapRule !== null) return $mapRule;
    $default = $cfg['default'][$action] ?? null;
    if ($default !== null) return $default;
    return $action === 'read' ? '*' : 'authenticated';
  }

  public static function canRead(string $mapKey, ?string $user): bool {
    return self::matches(self::rule($mapKey, 'read'), self::subjects($user));
  }

  public static function canEdit(string $mapKey, ?string $user): bool {
    return self::matches(self::rule($mapKey, 'edit'), self::subjects($user));
  }

  /**
   * @param array<string,array> $dirs
   * @return array<string,array>
   */
  public static function readableMaps(array $dirs, ?string $user): array {
    return array_filter($dirs, fn($key) => self::canRead((string)$key, $user), ARRAY_FILTER_USE_KEY);
  }

  /**
   * @param array<string,array> $dirs
   * @return array<string,array>
   */
  public static function editableMaps(array $dirs, ?string $user): array {
    return array_filter($dirs, fn($key) => self::canEdit((string)$key, $user), ARRAY_FILTER_USE_KEY);
  }
}
