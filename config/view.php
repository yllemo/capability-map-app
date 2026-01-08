<?php
return [
  // Which field to use for heat visualization on tiles
  'heat_field' => 'maturity', // maturity | criticality | risk_level
  // Optional: show lanes even if empty
  'show_empty_lanes' => true,
  
  // Layout configuration for capability grid
  'layout' => [
    // Maximum columns per layer section on different screen sizes
    // Dessa styr hur många kolumner som visas för förmågeområden
    'max_columns_sm' => 2,  // Small screens (mobil)
    'max_columns_md' => 3,  // Medium screens (tablet)  
    'max_columns_lg' => 4,  // Large screens (desktop)
    
    // Break to new row after X areas for better visual balance
    // (Reserverat för framtida användning)
    'break_after_areas' => 3,
  ],
];
