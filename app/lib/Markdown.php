<?php
declare(strict_types=1);

namespace App;

final class Markdown {
  public static function toHtml(string $md): string {
    $md = str_replace(["\r\n", "\r"], "\n", $md);
    $lines = explode("\n", $md);

    $html = '';
    $inUl = false;
    $inOl = false;
    $inBlockquote = false;
    $inCode = false;
    $codeLang = '';

    foreach ($lines as $line) {
      $raw = $line;

      // fenced code
      if (preg_match('/^```\s*([A-Za-z0-9_-]+)?\s*$/', $line, $m)) {
        if (!$inCode) {
          $inCode = true;
          $codeLang = $m[1] ?? '';
          if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
          if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
          if ($inBlockquote) { $html .= "</blockquote>\n"; $inBlockquote = false; }
          $html .= '<pre><code' . ($codeLang ? ' data-lang="' . htmlspecialchars($codeLang) . '"' : '') . '>';
        } else {
          $inCode = false;
          $html .= "</code></pre>\n";
        }
        continue;
      }

      if ($inCode) {
        $html .= htmlspecialchars($raw) . "\n";
        continue;
      }

      $trim = trim($line);

      // horizontal rule
      if (preg_match('/^([-_*])\1{2,}$/', $trim)) {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
        if ($inBlockquote) { $html .= "</blockquote>\n"; $inBlockquote = false; }
        $html .= "<hr>\n";
        continue;
      }

      // headings
      if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
        if ($inBlockquote) { $html .= "</blockquote>\n"; $inBlockquote = false; }
        $lvl = strlen($m[1]);
        $txt = self::inline($m[2]);
        $html .= "<h$lvl>$txt</h$lvl>\n";
        continue;
      }

      // blockquote
      if (preg_match('/^>\s?(.*)$/', $line, $m)) {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
        if (!$inBlockquote) { $html .= "<blockquote>\n"; $inBlockquote = true; }
        $html .= "<p>" . self::inline($m[1]) . "</p>\n";
        continue;
      } else {
        if ($inBlockquote) { $html .= "</blockquote>\n"; $inBlockquote = false; }
      }

      // ordered list
      if (preg_match('/^\s*\d+\.\s+(.*)$/', $line, $m)) {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
        if (!$inOl) { $html .= "<ol>\n"; $inOl = true; }
        $html .= "<li>" . self::inline($m[1]) . "</li>\n";
        continue;
      } else {
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
      }

      // unordered list
      if (preg_match('/^\s*[-*+]\s+(.*)$/', $line, $m)) {
        if ($inOl) { $html .= "</ol>\n"; $inOl = false; }
        if (!$inUl) { $html .= "<ul>\n"; $inUl = true; }
        $html .= "<li>" . self::inline($m[1]) . "</li>\n";
        continue;
      } else {
        if ($inUl) { $html .= "</ul>\n"; $inUl = false; }
      }

      if ($trim === '') {
        $html .= "\n";
        continue;
      }

      // paragraph
      $html .= "<p>" . self::inline($trim) . "</p>\n";
    }

    if ($inUl) $html .= "</ul>\n";
    if ($inOl) $html .= "</ol>\n";
    if ($inBlockquote) $html .= "</blockquote>\n";
    if ($inCode) $html .= "</code></pre>\n";

    return $html;
  }

  private static function inline(string $s): string {
    // Protect code and complete links before emphasis and automatic ID links.
    $tokens = [];
    $store = function(string $html) use (&$tokens): string {
      $key = "\x1A" . count($tokens) . "\x1A";
      $tokens[$key] = $html;
      return $key;
    };
    $s = str_replace("\x1A", '', $s);
    $escape = fn(string $value) => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $s = preg_replace_callback('/`([^`]+)`/', fn($m) => $store('<code>' . $escape($m[1]) . '</code>'), $s);
    $s = preg_replace_callback('/\[((?:\\\\.|[^\]\\\\])+)\]\(([^\s)]+)\)/', function($m) use ($store, $escape, &$tokens) {
      $label = preg_replace('/\\\\([\\\\\[\]])/', '$1', $m[1]);
      $target = $m[2];
      $internal = !preg_match('~^https?://~i', $target);
      if (preg_match('~^cap://([^/]+)/(.+)$~i', $target, $parts)) {
        $url = self::capUrl(rawurldecode($parts[2]), rawurldecode($parts[1]));
      } elseif ($internal) {
        $url = self::capUrl(preg_replace('/\.md$/i', '', $target));
      } else {
        $url = $target;
      }
      $labelHtml = strtr($escape($label), $tokens);
      return $store('<a href="' . $escape($url) . '" rel="noopener"' . ($internal ? ' class="cap-link"' : '') . '>' . $labelHtml . '</a>');
    }, $s);
    $s = $escape($s);
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $s);
    $s = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $s);
    $s = preg_replace('/_(.+?)_/s', '<em>$1</em>', $s);
    $parts = preg_split('/(<[^>]+>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
      if ($part === '' || $part[0] === '<') continue;
      $parts[$i] = preg_replace_callback('/\b(cap-[a-z0-9\-]+)\b/i', fn($m) => '<a href="' . $escape(self::capUrl($m[1])) . '" class="cap-link">' . $escape($m[1]) . '</a>', $part);
    }
    return strtr(implode('', $parts), $tokens);
  }

  private static function capUrl(string $id, ?string $map = null): string {
    if ($map === null && function_exists('get_selected_content_key')) $map = (string)get_selected_content_key();
    $path = 'view/capability.php?id=' . rawurlencode($id);
    if ($map !== null && $map !== '') $path .= '&map=' . rawurlencode($map);
    return function_exists('base_path') ? base_path($path) : '/' . $path;
  }
}
