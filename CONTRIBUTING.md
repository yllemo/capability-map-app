# Bidra till Capability Map App

Tack för ditt intresse att bidra till projektet! 

## Utvecklingsmiljö

### Förutsättningar
- PHP 8.0 eller senare
- Enkel webserver (inbyggd PHP-server fungerar)

### Installation för utveckling

1. Klona repot:
```bash
git clone https://github.com/yllemo/capability-map-app.git
cd capability-map-app
```

2. Ändra lösenord i config/auth.php:
```php
'editor_password' => 'ditt-utvecklingslösenord',
```

3. Starta utvecklingsserver:
4. Starta utvecklingsserver:
```bash
php -S localhost:8080
```

5. Öppna http://localhost:8080/view/index.php

## Kodstandard

### PHP
- Följ PSR-12 kodningsstandard
- Använd type hints där det är möjligt
- Kommentera komplicerade funktioner
- Håll funktioner små och fokuserade

### HTML/CSS
- Använd semantisk HTML
- Tailwind CSS för styling
- Mobile-first responsive design
- Tillgänglighet (a11y) är viktigt

### JavaScript
- Använd moderna ES6+ funktioner
- Ingen externa libraries om inte nödvändigt
- Kommentera komplicerad logik

## Filstruktur

```
/app/lib/           # Kärnklasser
/config/            # Konfigurationsfiler  
/assets/            # CSS, JS, ikoner
/view/              # Viewer-interface
/editor/            # Editor-interface
/content/           # Exempel-content
```

## Bidragsprocess

### Bug Reports
1. Kolla om problemet redan är rapporterat i Issues
2. Använd bug report template
3. Inkludera steg för att reproducera
4. Inkludera environment info (PHP version, OS)

### Feature Requests
1. Öppna en Issue först för diskussion
2. Förklara use case och fördelar
3. Överväg bakåtkompatibilitet

### Pull Requests
1. Fork repot
2. Skapa en feature branch: `git checkout -b feature/awesome-feature`
3. Gör dina ändringar
4. Testa funktionaliteten
5. Commit med beskrivande meddelanden
6. Push: `git push origin feature/awesome-feature`
7. Öppna en Pull Request

### Commit Messages
- Använd engelska
- Börja med verb i imperativ: "Add", "Fix", "Update"
- Håll första raden under 50 tecken
- Använd brödtext för längre förklaringar

Exempel:
```
Add filter button positioning fix

- Move filter panel to dropdown position under button
- Remove duplicate JavaScript event handlers
- Clean up CSS positioning classes
```

## Test

### Manuella tester
- Testa både viewer och editor
- Testa på olika skärmstorlekar
- Kontrollera dark/light mode
- Testa filter och sökfunktioner

### Browser-support
- Chrome/Chromium (senaste)
- Firefox (senaste)
- Safari (senaste)
- Edge (senaste)

## Säkerhet

- Granska säkerhetsriktlinjer i SECURITY.md
- Testa aldrig med produktionsdata
- Rapportera säkerhetsproblem privat

## Frågor?

- Öppna en Discussion för allmänna frågor
- Använd Issues för specifika problem
- Kontakta maintainer för känsliga frågor

Tack för ditt bidrag! 🚀