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
    $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // inline code `code` (must come before other formatting)
    $s = preg_replace('/`(.+?)`/', '<code>$1</code>', $s);

    // bold **text** or __text__
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s);

    // strikethrough ~~text~~
    $s = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $s);

    // italic *text* or _text_ (after bold to avoid conflicts)
    $s = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $s);
    $s = preg_replace('/_(.+?)_/s', '<em>$1</em>', $s);

    // links [text](url)
    // If target is not an absolute http(s) URL, treat it as a capability/file id.
    $s = preg_replace_callback('/\[(.+?)\]\((.+?)\)/s', function($matches) {
      $label = $matches[1];
      $rawTarget = html_entity_decode($matches[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $target = trim($rawTarget);

      if (!preg_match('/^https?:\/\//i', $target)) {
        $id = preg_replace('/\.md$/i', '', $target);
        $url = self::capUrl($id);
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" rel="noopener" class="cap-link">' . $label . '</a>';
      }

      return '<a href="' . htmlspecialchars($target, ENT_QUOTES) . '" rel="noopener">' . $label . '</a>';
    }, $s);

    // capability reference cap-xxx (only in text nodes, not inside HTML tags)
    $parts = preg_split('/(<[^>]+>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (is_array($parts)) {
      foreach ($parts as $i => $part) {
        if ($part === '' || $part[0] === '<') continue;
        $parts[$i] = preg_replace_callback('/\b(cap-[a-z0-9\-]+)\b/i', function($matches) {
          $capId = $matches[1];
          $url = self::capUrl($capId);
          return '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" class="cap-link">' . htmlspecialchars($capId) . '</a>';
        }, $part);
      }
      $s = implode('', $parts);
    }

    return $s;
  }

  private static function capUrl(string $id): string {
    // Use base_path if available (from bootstrap.php)
    if (function_exists('base_path')) {
      $path = 'view/capability.php?id=' . rawurlencode($id);
      if (function_exists('get_selected_content_key')) {
        $map = (string)get_selected_content_key();
        if ($map !== '') {
          $path .= '&map=' . rawurlencode($map);
        }
      }
      return base_path($path);
    }
    // Fallback for when base_path is not available
    return '/view/capability.php?id=' . rawurlencode($id);
  }
}
