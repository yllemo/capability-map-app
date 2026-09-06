<div class="interface-toggle" role="group" aria-label="Kartans gränssnitt">
  <a href="<?= h(base_path('view/index.php?map=' . rawurlencode($selectedKey) . '&interface=classic')) ?>" <?= $mapInterface === 'classic' ? 'aria-current="true"' : '' ?>><span aria-hidden="true">▦</span> Klassisk</a>
  <a href="<?= h(base_path('view/overview.php?map=' . rawurlencode($selectedKey) . '&interface=new')) ?>" <?= $mapInterface === 'new' ? 'aria-current="true"' : '' ?>><span aria-hidden="true">▤</span> Ny vy</a>
</div>
