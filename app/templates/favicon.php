<?php
$faviconVersion = (string)filemtime(__DIR__ . '/../../assets/favicon.svg');
?>
<link rel="icon" type="image/svg+xml" sizes="any" href="<?= h(base_path('assets/favicon.svg') . '?v=' . $faviconVersion) ?>">
