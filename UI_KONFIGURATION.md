# UI Konfiguration

Du kan nu anpassa texterna och loggan för Förmågekartan via config-filer.

## Anpassa texter

Redigera `/config/ui.php` för att ändra:

- **Huvudtitel**: `'title' => 'Din titel'`
- **Undertitel**: `'subtitle' => 'Din undertitel'`  
- **Heat-etikett**: `'heat_label' => 'din etikett'`
- **Knapptexter**: Sök, Filter, Export, Print
- **Export-alternativ**: Beskrivningar för export-dropdown

### Exempel på textändringar:

```php
return [
    'title' => 'Företagets Förmågekarta',
    'subtitle' => 'Arkitektur & Strategi',
    'heat_label' => 'mognad',
    'search_placeholder' => 'Sök efter förmågor...',
    'filter_button_text' => 'Filtrera',
    'export_excel_text' => 'Ladda ner Excel',
    // ... etc
];
```

## Anpassa logo

### Användning av egen SVG-logo:

1. **Skapa din SVG-fil** och lägg den i `/config/` mappen
2. **Uppdatera konfigurationen** i `/config/ui.php`:
   ```php
   'logo' => [
       'svg_file' => 'min-logo.svg', // Din SVG-fil
       'svg_width' => '40',
       'svg_height' => '40'
   ]
   ```

### Fallback-logo:

Om ingen SVG-fil anges används en text-logo:
```php
'logo' => [
    'svg_file' => null,
    'fallback_text' => 'MI', // Dina initialer
    'container_classes' => 'bg-blue-600 w-10 h-10 rounded flex items-center justify-center text-white font-bold shadow-sm'
]
```

## Exempel-logo

En exempel-SVG (`example-logo.svg`) finns i config-mappen som du kan använda som utgångspunkt.

För att testa den, ändra:
```php
'svg_file' => 'example-logo.svg'
```

## Tips

- **SVG-filer** ska vara optimerade och utan external dependencies
- **Container-klasser** använder Tailwind CSS syntax
- **Ändringar** träder i kraft direkt - ingen restart behövs
- **Backup**: Ta backup på original-config innan du gör stora ändringar