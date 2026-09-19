<?php
declare(strict_types=1);

namespace App;

/**
 * Small, dependency-free Markdown renderer (no composer/vendor in this
 * project). Supports the common block/inline syntax used in capability
 * descriptions: headings (ATX + setext), paragraphs with soft/hard breaks,
 * nested/ordered/task lists, multi-paragraph blockquotes, fenced code
 * (with a special "mermaid" language that renders as <pre class="mermaid">
 * for client-side Mermaid.js), GFM tables with column alignment, images,
 * autolinks, emphasis/strikethrough/code spans, and the app's own
 * cap:// / cap-id capability linking.
 */
final class Markdown {
  public static function toHtml(string $md): string {
    $md = str_replace(["\r\n", "\r"], "\n", $md);
    return self::blocks(explode("\n", $md));
  }

  /** @param array<int,string> $lines */
  private static function blocks(array $lines): string {
    $html = '';
    $i = 0;
    $n = count($lines);

    while ($i < $n) {
      $line = $lines[$i];
      $trim = trim($line);

      if ($trim === '') { $i++; continue; }

      // Fenced code block (``` or ~~~), keeps its own language tag.
      if (preg_match('/^ {0,3}(`{3,}|~{3,})\s*([A-Za-z0-9_+.\-]*)/', $line, $m)) {
        $fenceChar = $m[1][0];
        $fenceLen = strlen($m[1]);
        $lang = strtolower(trim($m[2] ?? ''));
        $i++;
        $codeLines = [];
        while ($i < $n) {
          if (preg_match('/^ {0,3}' . preg_quote($fenceChar, '/') . '{' . $fenceLen . ',}\s*$/', $lines[$i])) { $i++; break; }
          $codeLines[] = $lines[$i];
          $i++;
        }
        $code = implode("\n", $codeLines);
        if ($lang === 'mermaid') {
          $html .= '<pre class="mermaid">' . self::escape($code) . "</pre>\n";
        } else {
          $langAttr = $lang !== '' ? ' class="language-' . self::escape($lang) . '" data-lang="' . self::escape($lang) . '"' : '';
          $html .= '<pre><code' . $langAttr . '>' . self::escape($code) . "</code></pre>\n";
        }
        continue;
      }

      // Thematic break (---, ***, ___), checked before headings/lists.
      if (preg_match('/^ {0,3}([-_*])( *\1){2,} *$/', $trim)) {
        $html .= "<hr>\n";
        $i++;
        continue;
      }

      // ATX heading, optionally closed with trailing #'s ("## Title ##").
      if (preg_match('/^ {0,3}(#{1,6})(?:\s+(.*?))?\s*$/', $line, $m)) {
        $lvl = strlen($m[1]);
        $text = trim((string)($m[2] ?? ''));
        $text = preg_replace('/\s+#+\s*$/', '', $text);
        $html .= "<h$lvl>" . self::inline($text) . "</h$lvl>\n";
        $i++;
        continue;
      }

      // Setext heading: a single text line directly followed by an === or --- underline.
      if (
        $i + 1 < $n
        && preg_match('/^ {0,3}(=+|-+) *$/', $lines[$i + 1])
        && !self::listItemMatch($line)
        && !preg_match('/^ {0,3}>/', $line)
      ) {
        $level = $lines[$i + 1][0] === '=' ? 1 : 2;
        $html .= "<h$level>" . self::inline($trim) . "</h$level>\n";
        $i += 2;
        continue;
      }

      // Blockquote: gather contiguous ">" lines, then recurse on their content
      // so nested paragraphs/lists/quotes inside the quote work naturally.
      if (preg_match('/^ {0,3}>\s?(.*)$/', $line)) {
        $quoteLines = [];
        while ($i < $n && preg_match('/^ {0,3}>\s?(.*)$/', $lines[$i], $mm)) {
          $quoteLines[] = $mm[1];
          $i++;
        }
        $html .= "<blockquote>\n" . self::blocks($quoteLines) . "</blockquote>\n";
        continue;
      }

      // GFM table: a header row followed by a delimiter row of ---/:---/---:.
      if ($i + 1 < $n && strpos($line, '|') !== false && self::isTableDelimiter($lines[$i + 1])) {
        [$tableHtml, $consumed] = self::parseTable(array_slice($lines, $i));
        $html .= $tableHtml;
        $i += $consumed;
        continue;
      }

      // List (ordered/unordered), including nested sub-lists and task items.
      if (self::listItemMatch($line)) {
        [$listHtml, $consumed] = self::parseList(array_slice($lines, $i));
        $html .= $listHtml;
        $i += $consumed;
        continue;
      }

      // Paragraph: consume lines until a blank line or the start of another block.
      $paraLines = [$line];
      $i++;
      while ($i < $n) {
        $l = $lines[$i];
        $t = trim($l);
        if ($t === '') break;
        if (
          self::listItemMatch($l)
          || preg_match('/^ {0,3}>/', $l)
          || preg_match('/^ {0,3}(`{3,}|~{3,})/', $l)
          || preg_match('/^ {0,3}#{1,6}(\s|$)/', $l)
          || preg_match('/^ {0,3}([-_*])( *\1){2,} *$/', $t)
          || (strpos($l, '|') !== false && $i + 1 < $n && self::isTableDelimiter($lines[$i + 1]))
        ) break;
        $paraLines[] = $l;
        $i++;
      }
      $html .= '<p>' . self::joinParagraphLines($paraLines) . "</p>\n";
    }

    return $html;
  }

  /**
   * Parses a run of list items starting at $lines[0] (an unordered or
   * ordered marker). Sub-lines indented past the marker belong to that
   * item and are recursively parsed, which is what gives us nested lists,
   * multi-paragraph items and code/blockquotes inside list items for free.
   * @param array<int,string> $lines
   * @return array{0:string,1:int}
   */
  private static function parseList(array $lines): array {
    $n = count($lines);
    $first = self::listItemMatch($lines[0]);
    $ordered = $first['ordered'];
    $baseIndent = $first['indent'];

    $items = [];
    $i = 0;
    while ($i < $n) {
      $m = self::listItemMatch($lines[$i]);
      if ($m === null || $m['indent'] !== $baseIndent || $m['ordered'] !== $ordered) break;

      $contentIndent = $m['indent'] + strlen($m['marker']) + 1;
      $itemLines = [$m['content']];
      $i++;

      while ($i < $n) {
        $l = $lines[$i];
        if (trim($l) === '') {
          $next = $lines[$i + 1] ?? null;
          if ($next !== null && (self::indentOf($next) >= $contentIndent || self::listItemMatch($next) !== null)) {
            $itemLines[] = '';
            $i++;
            continue;
          }
          break;
        }
        if (self::indentOf($l) >= $contentIndent) {
          $itemLines[] = substr($l, min(strlen($l), $contentIndent));
          $i++;
          continue;
        }
        break;
      }

      while ($itemLines && trim(end($itemLines)) === '') array_pop($itemLines);
      $items[] = $itemLines;
    }

    $tag = $ordered ? 'ol' : 'ul';
    $attrs = ($ordered && $first['start'] !== 1) ? ' start="' . (int)$first['start'] . '"' : '';
    $html = "<$tag$attrs>\n";
    foreach ($items as $itemLines) {
      $isTask = false;
      $checked = false;
      if (isset($itemLines[0]) && preg_match('/^\[( |x|X)\]\s+(.*)$/', $itemLines[0], $tm)) {
        $isTask = true;
        $checked = strtolower($tm[1]) === 'x';
        $itemLines[0] = $tm[2];
      }

      $hasBlank = false;
      foreach ($itemLines as $l) { if (trim($l) === '') { $hasBlank = true; break; } }

      $inner = self::blocks($itemLines);
      // Keep "tight" single-paragraph items from getting wrapped in <p>,
      // matching how lists normally read (only loose/multi-block items
      // keep their <p> wrapper).
      if (!$hasBlank && preg_match('/^<p>(.*)<\/p>\n?$/s', $inner, $pm) && strpos($inner, '<ul') === false && strpos($inner, '<ol') === false) {
        $inner = $pm[1];
      }

      $liClass = $isTask ? ' class="task-list-item"' : '';
      $checkbox = $isTask ? '<input type="checkbox" disabled' . ($checked ? ' checked' : '') . '> ' : '';
      $html .= "<li$liClass>$checkbox$inner</li>\n";
    }
    $html .= "</$tag>\n";

    return [$html, $i];
  }

  /** @return array{indent:int,marker:string,ordered:bool,start:int,content:string}|null */
  private static function listItemMatch(string $line): ?array {
    if (!preg_match('/^( {0,3})([-*+]|(\d{1,9})[.)])\s+(.*)$/', $line, $m)) return null;
    return [
      'indent' => strlen($m[1]),
      'marker' => $m[2],
      'ordered' => $m[3] !== '',
      'start' => $m[3] !== '' ? (int)$m[3] : 1,
      'content' => $m[4],
    ];
  }

  private static function indentOf(string $line): int {
    return strlen($line) - strlen(ltrim($line, ' '));
  }

  private static function isTableDelimiter(string $line): bool {
    $t = trim($line);
    if ($t === '' || strpos($t, '-') === false || strpos($t, '|') === false) return false;
    return (bool)preg_match('/^\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)*\|?$/', $t);
  }

  /** @return array<int,string> */
  private static function splitTableRow(string $line): array {
    $line = trim($line);
    $line = preg_replace('/^\|/', '', $line);
    $line = preg_replace('/(?<!\\\\)\|$/', '', $line);
    $cells = preg_split('/(?<!\\\\)\|/', $line);
    return array_map(fn($c) => str_replace('\\|', '|', trim($c)), $cells);
  }

  /**
   * @param array<int,string> $lines
   * @return array{0:string,1:int}
   */
  private static function parseTable(array $lines): array {
    $header = self::splitTableRow($lines[0]);
    $delimCells = self::splitTableRow($lines[1]);
    $aligns = array_map(function (string $d): string {
      $d = trim($d);
      $left = str_starts_with($d, ':');
      $right = str_ends_with($d, ':');
      if ($left && $right) return 'center';
      if ($right) return 'right';
      if ($left) return 'left';
      return '';
    }, $delimCells);

    $n = count($lines);
    $rows = [];
    $i = 2;
    while ($i < $n && trim($lines[$i]) !== '' && strpos($lines[$i], '|') !== false && !self::listItemMatch($lines[$i])) {
      $rows[] = self::splitTableRow($lines[$i]);
      $i++;
    }

    $cellAttr = function (int $idx) use ($aligns): string {
      $align = $aligns[$idx] ?? '';
      return $align !== '' ? ' style="text-align:' . $align . '"' : '';
    };

    $html = '<div class="table-wrap"><table>' . "\n<thead>\n<tr>";
    foreach ($header as $idx => $cell) {
      $html .= '<th' . $cellAttr($idx) . '>' . self::inline($cell) . '</th>';
    }
    $html .= "</tr>\n</thead>\n<tbody>\n";
    foreach ($rows as $row) {
      $html .= '<tr>';
      foreach ($header as $idx => $_) {
        $cell = $row[$idx] ?? '';
        $html .= '<td' . $cellAttr($idx) . '>' . self::inline($cell) . '</td>';
      }
      $html .= "</tr>\n";
    }
    $html .= "</tbody>\n</table></div>\n";

    return [$html, $i];
  }

  /** @param array<int,string> $lines */
  private static function joinParagraphLines(array $lines): string {
    $html = '';
    $count = count($lines);
    for ($idx = 0; $idx < $count; $idx++) {
      $line = $lines[$idx];
      $isLast = $idx === $count - 1;
      $hardBreak = !$isLast && (bool)preg_match('/(?:  +|\\\\)$/', $line);
      $html .= self::inline(trim($line));
      if (!$isLast) $html .= $hardBreak ? "<br>\n" : "\n";
    }
    return $html;
  }

  private static function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

  private static function inline(string $s): string {
    // Protect code/links/images/autolinks before emphasis and automatic ID
    // links run, so markup inside them is never re-parsed as text.
    $tokens = [];
    $store = function (string $html) use (&$tokens): string {
      $key = "\x1A" . count($tokens) . "\x1A";
      $tokens[$key] = $html;
      return $key;
    };
    $s = str_replace("\x1A", '', $s);
    $escape = fn(string $value) => self::escape($value);

    // Code spans.
    $s = preg_replace_callback('/`([^`]+)`/', fn($m) => $store('<code>' . $escape(trim($m[1])) . '</code>'), $s);

    // Images (must run before links, since ![...]() also matches [...]()).
    $s = preg_replace_callback(
      '/!\[((?:\\\\.|[^\]\\\\])*)\]\(\s*([^\s)]+)(?:\s+"([^"]*)")?\s*\)/',
      function ($m) use ($store, $escape) {
        $alt = preg_replace('/\\\\([\\\\\[\]])/', '$1', $m[1]);
        $titleAttr = isset($m[3]) && $m[3] !== '' ? ' title="' . $escape($m[3]) . '"' : '';
        return $store('<img src="' . $escape($m[2]) . '" alt="' . $escape($alt) . '"' . $titleAttr . ' loading="lazy">');
      },
      $s
    );

    // Links, including this app's cap:// / *.md capability links.
    $s = preg_replace_callback(
      '/\[((?:\\\\.|[^\]\\\\])+)\]\(\s*([^\s)]+)(?:\s+"([^"]*)")?\s*\)/',
      function ($m) use ($store, $escape, &$tokens) {
        $label = preg_replace('/\\\\([\\\\\[\]])/', '$1', $m[1]);
        $target = $m[2];
        $title = $m[3] ?? '';
        $internal = !preg_match('~^https?://~i', $target);
        if (preg_match('~^cap://([^/]+)/(.+)$~i', $target, $parts)) {
          $url = self::capUrl(rawurldecode($parts[2]), rawurldecode($parts[1]));
        } elseif ($internal) {
          $url = self::capUrl(preg_replace('/\.md$/i', '', $target));
        } else {
          $url = $target;
        }
        $labelHtml = strtr($escape($label), $tokens);
        $titleAttr = $title !== '' ? ' title="' . $escape($title) . '"' : '';
        $extAttr = $internal ? '' : ' target="_blank"';
        return $store('<a href="' . $escape($url) . '" rel="noopener"' . $titleAttr . $extAttr . ($internal ? ' class="cap-link"' : '') . '>' . $labelHtml . '</a>');
      },
      $s
    );

    // Angle-bracket autolinks: <https://...> and <mailto:...>.
    $s = preg_replace_callback('/<((?:https?:\/\/|mailto:)[^\s<>]+)>/i', function ($m) use ($store, $escape) {
      $url = $m[1];
      $isMail = stripos($url, 'mailto:') === 0;
      $label = $isMail ? substr($url, 7) : $url;
      return $store('<a href="' . $escape($url) . '" rel="noopener"' . ($isMail ? '' : ' target="_blank"') . '>' . $escape($label) . '</a>');
    }, $s);

    // Bare URL autolinks (only outside already-tokenized markup).
    $s = preg_replace_callback('/\bhttps?:\/\/[^\s<>()\x1A]+[^\s<>()".,;:!?\x1A]/', function ($m) use ($store, $escape) {
      return $store('<a href="' . $escape($m[0]) . '" rel="noopener" target="_blank">' . $escape($m[0]) . '</a>');
    }, $s);

    $s = $escape($s);
    $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s);
    $s = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $s);
    $s = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $s);
    $s = preg_replace('/(?<![A-Za-z0-9_])_(.+?)_(?![A-Za-z0-9_])/s', '<em>$1</em>', $s);

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
