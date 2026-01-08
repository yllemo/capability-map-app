<?php
return [
  'layers' => [
    'ledning_styrning' => 'Ledning & styrning',
    'karnprocesser'    => 'Kärnprocesser',
    'verksamhetsstod'  => 'Verksamhetsstöd',
  ],
  
  // Konfigurerbar namngivning för layer-visning i view
  // Dessa namn visas som rubriker i förmågekartan (/view/index.php)
  // Ändra texterna här för att anpassa rubrikerna till din organisation
  'layer_display_names' => [
    'ledning_styrning' => 'Styrande & Ledning',
    'karnprocesser'    => 'Kärnverksamhet', 
    'verksamhetsstod'  => 'Stödjande Processer',
  ],
  
  'types' => [
    'verksamhetsformaga' => 'Verksamhetsförmåga',
    'stodformaga'        => 'Stödförmåga',
  ],
  'levels' => [1,2,3],
];
