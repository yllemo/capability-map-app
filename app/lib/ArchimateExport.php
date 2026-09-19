<?php
declare(strict_types=1);

namespace App;

/** Builds an ArchiMate 3.1 Open Exchange Format XML document from capabilities. */
final class ArchimateExport {
  /** @param array<Capability> $caps */
  public static function build(array $caps, array $tax, string $modelName, bool $prefixSource = false): string {
    $used = [];
    $idFor = function (string $raw) use (&$used): string {
      $base = preg_replace('/[^A-Za-z0-9_.\-]/', '_', $raw);
      if ($base === null || $base === '' || !preg_match('/^[A-Za-z_]/', $base)) $base = 'id_' . $base;
      $candidate = $base;
      $i = 2;
      while (isset($used[$candidate])) $candidate = $base . '_' . $i++;
      $used[$candidate] = true;
      return $candidate;
    };

    $layerLabel = function (string $layer) use ($tax): string {
      if ($layer === '') return 'Övrigt';
      return (string)($tax['layer_display_names'][$layer] ?? $tax['layers'][$layer] ?? $layer);
    };

    $elements = [];
    $relationships = [];
    $layerIds = [];
    $areaIds = [];
    /** @var array<string,string> $capIds raw dependency-lookup key => xml identifier */
    $capIds = [];

    foreach ($caps as $cap) {
      $layer = $cap->layer !== '' ? $cap->layer : 'unknown';
      $area = $cap->area !== '' ? $cap->area : 'Övrigt';
      $source = $prefixSource ? (string)($cap->_source_dir ?? '') : '';

      if (!isset($layerIds[$layer])) {
        $lid = $idFor('layer-' . $layer);
        $layerIds[$layer] = $lid;
        $elements[] = ['id' => $lid, 'type' => 'Grouping', 'name' => $layerLabel($layer), 'doc' => ''];
      }

      $areaKey = $layer . '|' . $area;
      if (!isset($areaIds[$areaKey])) {
        $aid = $idFor('area-' . $layer . '-' . $area);
        $areaIds[$areaKey] = $aid;
        $elements[] = ['id' => $aid, 'type' => 'Grouping', 'name' => $area, 'doc' => ''];
        $relationships[] = ['id' => $idFor('rel-' . $layerIds[$layer] . '-' . $aid), 'type' => 'Composition', 'source' => $layerIds[$layer], 'target' => $aid, 'name' => ''];
      }

      $depKey = $source . '|' . $cap->id;
      $capXmlId = $idFor($source !== '' ? $source . '-' . $cap->id : $cap->id);
      $capIds[$depKey] = $capXmlId;
      $elements[] = ['id' => $capXmlId, 'type' => 'Capability', 'name' => $cap->name, 'doc' => $cap->description];
      $relationships[] = ['id' => $idFor('rel-' . $areaIds[$areaKey] . '-' . $capXmlId), 'type' => 'Composition', 'source' => $areaIds[$areaKey], 'target' => $capXmlId, 'name' => ''];
    }

    foreach ($caps as $cap) {
      $deps = $cap->get('dependencies', []);
      if (!is_array($deps)) $deps = $deps !== null && $deps !== '' ? [$deps] : [];
      if (!$deps) continue;
      $source = $prefixSource ? (string)($cap->_source_dir ?? '') : '';
      $sourceKey = $source . '|' . $cap->id;
      if (!isset($capIds[$sourceKey])) continue;
      foreach ($deps as $dep) {
        if (!is_scalar($dep)) continue;
        $targetKey = $source . '|' . (string)$dep;
        if (!isset($capIds[$targetKey]) || $targetKey === $sourceKey) continue;
        $relationships[] = [
          'id' => $idFor('rel-dep-' . $capIds[$sourceKey] . '-' . $capIds[$targetKey]),
          'type' => 'Association',
          'source' => $capIds[$sourceKey],
          'target' => $capIds[$targetKey],
          'name' => 'beror på',
        ];
      }
    }

    return self::render($modelName, $elements, $relationships);
  }

  /**
   * @param array<array{id:string,type:string,name:string,doc:string}> $elements
   * @param array<array{id:string,type:string,source:string,target:string,name:string}> $relationships
   */
  private static function render(string $modelName, array $elements, array $relationships): string {
    $esc = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<model xmlns="http://www.opengroup.org/xsd/archimate/3.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" identifier="'
      . $esc('model-' . substr(sha1($modelName . microtime()), 0, 12)) . '" version="3.1">' . "\n";
    $xml .= '  <name>' . $esc($modelName) . '</name>' . "\n";

    $xml .= "  <elements>\n";
    foreach ($elements as $el) {
      $xml .= '    <element identifier="' . $esc($el['id']) . '" xsi:type="' . $esc($el['type']) . '">' . "\n";
      $xml .= '      <name>' . $esc($el['name'] !== '' ? $el['name'] : $el['id']) . '</name>' . "\n";
      if ($el['doc'] !== '') $xml .= '      <documentation>' . $esc($el['doc']) . '</documentation>' . "\n";
      $xml .= "    </element>\n";
    }
    $xml .= "  </elements>\n";

    $xml .= "  <relationships>\n";
    foreach ($relationships as $rel) {
      $xml .= '    <relationship identifier="' . $esc($rel['id']) . '" xsi:type="' . $esc($rel['type'])
        . '" source="' . $esc($rel['source']) . '" target="' . $esc($rel['target']) . '">' . "\n";
      if ($rel['name'] !== '') $xml .= '      <name>' . $esc($rel['name']) . '</name>' . "\n";
      $xml .= "    </relationship>\n";
    }
    $xml .= "  </relationships>\n";

    $xml .= "</model>\n";
    return $xml;
  }
}
