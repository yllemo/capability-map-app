<?php
declare(strict_types=1);

namespace App;

/**
 * Editor accounts and the signed login cookie.
 *
 * Accounts live in config/auth.php under 'users' (username => name + password
 * hash). A legacy single shared password ('editor_password') is still
 * supported for old installs and logs everyone in as the built-in "editor"
 * user, so upgrading never locks anyone out.
 */
final class Auth {
  private const LEGACY_USER = 'editor';

  /** @var array{user:?string}|null */
  private static ?array $resolved = null;

  public static function cookieName(): string {
    return (string)(\cfg('auth')['cookie_name'] ?? 'capmap_editor');
  }

  public static function ttl(): int {
    return (int)(\cfg('auth')['cookie_ttl'] ?? 60 * 60 * 8);
  }

  private static function secret(): string {
    return (string)(\cfg('auth')['session_secret'] ?? '');
  }

  /** @return array<string,array{name?:string,password_hash?:string,password?:string}> */
  public static function users(): array {
    $users = \cfg('auth')['users'] ?? [];
    return is_array($users) ? $users : [];
  }

  private static function legacyPassword(): string {
    return (string)(\cfg('auth')['editor_password'] ?? '');
  }

  /** True when no accounts and no legacy password are configured: the installation is wide open. */
  public static function isOpen(): bool {
    return self::users() === [] && self::legacyPassword() === '';
  }

  private static function knownUser(string $username): bool {
    if (isset(self::users()[$username])) return true;
    return $username === self::LEGACY_USER && self::legacyPassword() !== '' && !isset(self::users()[self::LEGACY_USER]);
  }

  /** @return list<string> usernames that can be entered at the login form. */
  public static function loginableUsers(): array {
    $names = array_keys(self::users());
    if (self::legacyPassword() !== '' && !isset(self::users()[self::LEGACY_USER])) $names[] = self::LEGACY_USER;
    return $names;
  }

  public static function verifyCredentials(string $username, string $password): ?string {
    if ($username === '') return null;
    $users = self::users();
    if ($username === self::LEGACY_USER && self::legacyPassword() !== '' && !isset($users[self::LEGACY_USER])) {
      return hash_equals(self::legacyPassword(), $password) ? self::LEGACY_USER : null;
    }
    $user = $users[$username] ?? null;
    if (!is_array($user)) return null;
    $hash = $user['password_hash'] ?? null;
    if (is_string($hash) && $hash !== '') {
      return password_verify($password, $hash) ? $username : null;
    }
    $plain = $user['password'] ?? null;
    if (is_string($plain) && $plain !== '') {
      return hash_equals($plain, $password) ? $username : null;
    }
    return null;
  }

  public static function displayName(string $username): string {
    $name = self::users()[$username]['name'] ?? null;
    if (is_string($name) && $name !== '') return $name;
    return $username === self::LEGACY_USER ? 'Editor' : $username;
  }

  private static function sign(string $username, int $expires): string {
    return hash_hmac('sha256', $username . '|' . $expires, self::secret());
  }

  public static function issueCookie(string $username): void {
    $expires = time() + self::ttl();
    $value = $username . '|' . $expires . '|' . self::sign($username, $expires);
    setcookie(self::cookieName(), $value, [
      'expires' => $expires,
      'path' => \base_path('/'),
      'httponly' => true,
      'samesite' => 'Lax',
      'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    self::$resolved = ['user' => $username];
  }

  public static function clearCookie(): void {
    setcookie(self::cookieName(), '', time() - 3600, \base_path('/'));
    self::$resolved = ['user' => null];
  }

  /** The logged-in username, or null. Always null while isOpen() (no accounts configured at all). */
  public static function currentUser(): ?string {
    if (self::isOpen()) return null;
    if (self::$resolved !== null) return self::$resolved['user'];

    $cookie = $_COOKIE[self::cookieName()] ?? null;
    if (is_string($cookie) && $cookie !== '') {
      $parts = explode('|', $cookie, 3);
      if (count($parts) === 3) {
        [$user, $expiresRaw, $sig] = $parts;
        $expires = (int)$expiresRaw;
        if ($expires >= time() && self::knownUser($user) && hash_equals(self::sign($user, $expires), $sig)) {
          self::$resolved = ['user' => $user];
          return $user;
        }
      }
    }

    self::$resolved = ['user' => null];
    return null;
  }
}
