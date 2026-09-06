<?php
return [
  'site_name' => 'Capability Maps',
  'base_path' => '', // e.g. '/capapp' if hosted in a subfolder
  'content_dir' => __DIR__ . '/../content', // deprecated, use content_dirs
  // Legacy paths below remain active until editor/migrate_content.php is run.
  // The migration writes config/content.local.json with a common content_root
  // and per-map folder names. That local configuration then takes precedence.
  'storage_dir' => __DIR__ . '/../storage',
  'default_mode' => 'view', // view | editor
  'timezone' => 'Europe/Stockholm',

  // Multiple content directories support
  // Add more directories here to enable folder switching
  'content_dirs' => [
    'content' => [
      'path' => __DIR__ . '/../content',
      'label' => 'Huvudkatalog',
      'description' => 'Standard förmågekartor',
    ],
    'content2' => [
      'path' => __DIR__ . '/../content2',
      'label' => 'Alternativ katalog',
      'description' => 'Testmiljö',
    ],
  ],
];
