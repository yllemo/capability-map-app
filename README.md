# Capability Map App (PHP + Markdown)

En fullständig Enterprise Architecture capability map-applikation byggd med PHP och Markdown.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)

## ⚡ Snabbstart

```bash
# Klona och starta lokalt
git clone https://github.com/yllemo/capability-map-app.git
cd capability-map-app

# Starta utvecklingsserver
php -S localhost:8080

# Öppna i webbläsare
open http://localhost:8080/view/index.php
```

**⚠️ VIKTIGT**: Ändra lösenord i `config/auth.php` innan du kör i produktion!

## Funktioner

### Viewer (`/view/index.php`)
- 📊 Interaktiv förmågekarta med layers och områden
- 🔍 Sök och filter på mognadsgrad
- 🎨 Dark/Light mode med automatisk tema-ihågkommelse
- 📸 PNG-export av kartan
- 📤 Excel-export av alla förmågor
- 📁 Multi-folder support - växla mellan olika innehållskataloger
- 🔗 Auto-linking av capability-referenser (cap-xxx) i markdown

### Editor (`/editor/index.php`)
- ✏️ Markdown-editor med live preview
- 📝 YAML frontmatter-redigering
- ➕ Skapa nya capabilities
- 🗑️ Radera filer med bekräftelse
- ✏️ Byt namn på filer
- 💾 Ladda ner enskilda filer eller hela foldern som ZIP
- 📁 Skapa och hantera flera content-folders
- ⚠️ Varning vid osparade ändringar
- ✅ Success/error feedback-meddelanden
- 🛡️ Validering av duplicerade ID:n

### Säkerhet
- 🔐 Session-baserad autentisering för editor
- 🛡️ CSRF-skydd på alla formulär
- 🔒 Säker session-konfiguration (httponly, samesite, secure)
- 📝 Audit logging av alla ändringar
- ⚠️ Varning vid användning av standardlösenord
- 🚫 Path traversal-skydd med PathGuard

## Kom igång (lokalt)

```bash
php -S localhost:8080
```

Öppna:
- **Viewer**: http://localhost:8080/view/index.php
- **Editor**: http://localhost:8080/editor/index.php

### Första gången
1. **VIKTIGT**: Ändra lösenord i `/config/auth.php` 
   ```php
   'editor_password' => 'ÄNDRA-TILL-SÄKERT-LÖSENORD-HÄR',
   ```
2. Logga in på editorn: http://localhost:8080/editor/index.php
3. Börja skapa eller redigera capabilities

> 🔐 **Säkerhetstips**: Använd minst 32 tecken långt slumpmässigt lösenord för produktion!

## Konfiguration

### Auth (`/config/auth.php`)
```php
'editor_password' => 'ditt-säkra-lösenord-här',  // Ändra detta!
'cookie_name' => 'capmap_editor',
'cookie_ttl' => 60 * 60 * 8,  // 8 timmar
```

### App (`/config/app.php`)
```php
'site_name' => 'Capability Maps',
'base_path' => '',  // ex. '/capapp' om hostad i subfolder
'timezone' => 'Europe/Stockholm',

// Multi-folder support
'content_dirs' => [
  'content' => [
    'path' => __DIR__ . '/../content',
    'label' => 'Huvudkatalog',
    'description' => 'Standard förmågekartor',
  ],
  // Lägg till fler folders här
],
```

### Taxonomi (`/config/taxonomy.php`)
Definiera layers, types, och levels för din organisation.

## Frontmatter (YAML)

```yaml
---
id: cap-hr-001
name: Lönehantering
layer: karnprocesser
area: HR
level: 2
type: verksamhetsformaga
description: Hanterar löneprocessen från A till Ö
owner: HR-chef
status: aktiv
maturity: 3
criticality: 4
tags: [hr, lön, process]
updated: 2025-12-30
---

# Lönehantering

Markdown-innehåll här med full support för:
- Listor (ordered och unordered)
- **Fetstil** och *kursiv*
- `Kod` och kodblock
- > Citat
- [Länkar](https://example.com)
- Auto-linking till andra capabilities (cap-hr-002)
```

## Multi-folder support

Växla mellan olika innehållskataloger (t.ex. produktion, test, arkiv):

1. Lägg till i `config/app.php`:
```php
'content_dirs' => [
  'content' => [
    'path' => __DIR__ . '/../content',
    'label' => 'Produktion',
    'description' => 'Produktionsdata',
  ],
  'test' => [
    'path' => __DIR__ . '/../test',
    'label' => 'Test',
    'description' => 'Testmiljö',
  ],
],
```

2. Skapa mappen: `mkdir test`

3. Dropdown visas automatiskt i både viewer och editor

## Filstruktur

```
/content/               # Markdown-filer (capabilities)
  /ledning_styrning/
  /karnprocesser/
  /verksamhetsstod/
/editor/               # Editor-interface
/view/                 # Viewer-interface
/app/                  # Backend-logik
  /lib/                # Klasser (Repository, Markdown, etc.)
  /templates/          # Layout-templates
/config/               # Konfigurationsfiler
/storage/              # Logs och temp-filer
/assets/               # CSS, JS, ikoner
```

## Säkerhet & Best Practices

- ✅ Använd HTTPS i produktion
- ✅ Ändra standardlösenord omedelbart
- ✅ Säkerhetskopiera `/content` regelbundet
- ✅ Granska `/storage/error.log` för fel
- ✅ Begränsa åtkomst till `/editor` med .htaccess eller brandvägg
- ✅ Använd starka lösenord (minst 32 tecken)

## Loggar

Alla editor-operationer loggas i `/storage/error.log`:
- Skapande av capabilities
- Uppdateringar
- Borttagningar
- Namnbyten
- Folder-skapande

Format: `[YYYY-MM-DD HH:MM:SS] AUDIT: action (user: xxx) {context}`

## Deployment

### Apache
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/capability-map
    <Directory /var/www/capability-map>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Skydda känsliga kataloger
    <Directory /var/www/capability-map/storage>
        Require all denied
    </Directory>
    <Directory /var/www/capability-map/config>
        Require all denied
    </Directory>
</VirtualHost>
```

### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/capability-map;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
    
    # Skydda känsliga filer
    location ~ ^/(storage|config|\.git) {
        deny all;
        return 404;
    }
}
```

### Säkerhet
- Ändra lösenord i `config/auth.php` innan deployment
- Sätt korrekta filrättigheter: `chmod -R 755 ./ && chmod -R 700 storage/`
- Använd HTTPS i produktion
- Säkerhetskopiera `content/` regelbundet

## Licens

MIT License - använd fritt i din organisation.

## 🤝 Bidra

Vi välkomnar bidrag! Läs [CONTRIBUTING.md](CONTRIBUTING.md) för riktlinjer.

- 🐛 [Rapportera bugs](https://github.com/yllemo/capability-map-app/issues)
- 💡 [Föreslå features](https://github.com/yllemo/capability-map-app/issues)
- 🔧 [Skicka Pull Request](https://github.com/yllemo/capability-map-app/pulls)

## 📞 Support

- 📖 [Dokumentation](README.md)
- 🔒 [Säkerhetspolicy](SECURITY.md)
- 💬 [Diskussioner](https://github.com/yllemo/capability-map-app/discussions)
- 🐛 [Issue Tracker](https://github.com/yllemo/capability-map-app/issues)
