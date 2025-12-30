<?php
return [
  'site_name' => 'Capability Maps',
  'base_path' => '', // e.g. '/capapp' if hosted in a subfolder
  'content_dir' => __DIR__ . '/../content', // deprecated, use content_dirs
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
