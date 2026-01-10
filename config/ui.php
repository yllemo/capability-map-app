<?php

/**
 * UI Configuration
 * 
 * Detta filen låter dig anpassa texter och utseende för applikationen.
 * 
 * LOGO:
 * För att använda din egen SVG-logo:
 * 1. Lägg din SVG-fil i /config/ mappen (t.ex. "min-logo.svg")
 * 2. Ändra 'svg_file' => 'min-logo.svg'
 * 
 * TEXTER:
 * Ändra alla texter som visas i användargränssnittet.
 */

return [
    // Main title and branding
    'title' => 'Förmågekarta',
    'subtitle' => 'Vy: Strategisk mognad',
    'heat_label' => 'heat',
    
    // Logo configuration
    'logo' => [
        // SVG file path relative to config directory, or null to use default
        // Example: 'logo.svg' if you have config/logo.svg
        'svg_file' => null, // Change to 'example-logo.svg' to test custom logo
        // Fallback text when no SVG is available  
        'fallback_text' => 'EA',
        // CSS classes for the logo container
        'container_classes' => 'bg-inera-blue w-10 h-10 rounded flex items-center justify-center text-white font-bold shadow-sm',
        // Width and height for SVG (when used)
        'svg_width' => '40',
        'svg_height' => '40'
    ],
    
    // Page metadata
    'page_title' => 'Förmågekarta', // Used in <title> tag
    'favicon' => [
        'svg' => 'assets/favicon.svg',
        'png' => 'assets/favicon.png'
    ],
    
    // Search and filter labels
    'search_placeholder' => 'Sök förmågor...',
    'filter_button_text' => 'Filter',
    'export_excel_text' => 'Exportera till Excel',
    'print_button_text' => 'Skriv ut',
    'save_png_text' => 'Spara som PNG',
    
    // Export dropdown options
    'export_options' => [
        'current' => [
            'title' => 'Aktuell katalog',
            'description' => 'Endast denna vy'
        ],
        'all' => [
            'title' => 'Alla kataloger', 
            'description' => 'Alla tillgängliga data'
        ]
    ]
];