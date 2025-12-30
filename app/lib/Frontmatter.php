<?php
declare(strict_types=1);

namespace App;

final class Frontmatter {
  /** @return array{meta: array, body: string} */
  public static function parse(string $content): array {
    $content = ltrim($content, "\xEF\xBB\xBF"); // remove BOM
    if (preg_match('/^---\R(.*?)\R---\R(.*)$/s', $content, $m)) {
      $yaml = trim($m[1]);
      $body = $m[2];
      $meta = self::parseYamlLike($yaml);
      return ['meta' => $meta, 'body' => $body];
    }
    return ['meta' => [], 'body' => $content];
  }

  /**
   * Very small YAML subset parser:
   * - key: value
   * - key: [a, b]
   * - key: 
   *     - item
   *     - item
   * - key:
   *     - name: X
   *       target: Y
   */
  private static function parseYamlLike(string $yaml): array {
    $lines = preg_split('/\R/', $yaml);
    $meta = [];
    $i = 0;
    while ($i < count($lines)) {
      $line = rtrim($lines[$i]);
      if ($line === '' || str_starts_with(ltrim($line), '#')) { $i++; continue; }

      // key: value or key:
      if (preg_match('/^([A-Za-z0-9_\-]+):\s*(.*)$/', $line, $m)) {
        $key = $m[1];
        $rest = $m[2];

        if ($rest !== '') {
          $meta[$key] = self::parseScalar($rest);
          $i++;
          continue;
        }

        // Multiline list/object
        $i++;
        $items = [];
        while ($i < count($lines)) {
          $l = rtrim($lines[$i]);
          if ($l === '') { $i++; continue; }
          if (preg_match('/^([A-Za-z0-9_\-]+):\s*/', $l)) break; // next top-level

          // list item "- x" or "- key: val"
          if (preg_match('/^\s*-\s*(.*)$/', $l, $mm)) {
            $itemRest = $mm[1];
            if ($itemRest === '') {
              $items[] = '';
              $i++;
              continue;
            }

            // object-in-list start "- name: X"
            if (preg_match('/^([A-Za-z0-9_\-]+):\s*(.*)$/', $itemRest, $kv)) {
              $obj = [];
              $obj[$kv[1]] = self::parseScalar($kv[2]);
              $i++;
              // read indented key: val lines
              while ($i < count($lines)) {
                $l2 = rtrim($lines[$i]);
                if ($l2 === '') { $i++; continue; }
                if (preg_match('/^\s*-\s*/', $l2)) break;
                if (preg_match('/^\s{2,}([A-Za-z0-9_\-]+):\s*(.*)$/', $l2, $kv2)) {
                  $obj[$kv2[1]] = self::parseScalar($kv2[2]);
                  $i++;
                  continue;
                }
                break;
              }
              $items[] = $obj;
              continue;
            }

            $items[] = self::parseScalar($itemRest);
            $i++;
            continue;
          }

          $i++;
        }

        $meta[$key] = $items;
        continue;
      }

      $i++;
    }
    return $meta;
  }

  private static function parseScalar(string $v): mixed {
    $v = trim($v);
    // quoted
    if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
      return substr($v, 1, -1);
    }
    // inline array [a, b]
    if (str_starts_with($v, '[') && str_ends_with($v, ']')) {
      $inner = trim(substr($v, 1, -1));
      if ($inner === '') return [];
      $parts = array_map('trim', explode(',', $inner));
      return array_values(array_filter($parts, fn($x) => $x !== ''));
    }
    // bool/null/int/float
    $lower = strtolower($v);
    if ($lower === 'true') return true;
    if ($lower === 'false') return false;
    if ($lower === 'null' || $v === '~') return null;
    if (preg_match('/^-?\d+$/', $v)) return (int)$v;
    if (preg_match('/^-?\d+\.\d+$/', $v)) return (float)$v;

    return $v;
  }
}
