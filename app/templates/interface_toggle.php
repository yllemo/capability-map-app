<?php
// Renders the classic/new interface switch. Uses $mapInterfaceTargets when the
// including page set one (e.g. the capability detail pair), else falls back
// to the main map views. Preserves $id (capability detail pages) alongside
// the selected map.
$interfaceToggleTargets = $mapInterfaceTargets ?? [
  'classic' => 'view/index.php',
  'new' => 'view/overview.php',
];
$interfaceToggleQuery = ['map' => $selectedKey];
if (isset($id) && $id !== '') $interfaceToggleQuery['id'] = $id;
?>
<div class="interface-toggle" role="group" aria-label="Kartans gränssnitt">
  <a href="<?= h(base_path($interfaceToggleTargets['classic'] . '?' . http_build_query($interfaceToggleQuery + ['interface' => 'classic'], '', '&', PHP_QUERY_RFC3986))) ?>" <?= $mapInterface === 'classic' ? 'aria-current="true"' : '' ?>><span aria-hidden="true">▦</span> Klassisk</a>
  <a href="<?= h(base_path($interfaceToggleTargets['new'] . '?' . http_build_query($interfaceToggleQuery + ['interface' => 'new'], '', '&', PHP_QUERY_RFC3986))) ?>" <?= $mapInterface === 'new' ? 'aria-current="true"' : '' ?>><span aria-hidden="true">▤</span> Ny vy</a>
</div>
