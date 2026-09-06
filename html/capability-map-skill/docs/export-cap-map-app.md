# Export: Markdown/YAML för capability-map-app

Exportformat för [capability-map-app](https://github.com/yllemo/capability-map-app) —
en PHP-applikation som lagrar förmågor som Markdown-filer med YAML frontmatter.
Ingen databas. Förmågor är ren text, Git-versionshanterbara och AI-kompatibla.

Läs in detta dokument när användaren ber om export till capability-map-app, nämner
"Markdown med frontmatter", "YAML frontmatter", "PHP-appen", "cap-xxx-nnn",
eller hänvisar till github.com/yllemo/capability-map-app.

---

## Applikation

**Repo:** https://github.com/yllemo/capability-map-app  
**Stack:** PHP 8.0+, Markdown, YAML frontmatter — ingen databas  
**Snabbstart:**
```bash
git clone https://github.com/yllemo/capability-map-app.git
cd capability-map-app
php -S localhost:8080
# Viewer: http://localhost:8080/view/index.php
# Editor: http://localhost:8080/editor/index.php
```

**Filosofi:** Förmågor som lever. PowerPoint-kartor glöms bort inom sex månader.
Markdown + Git = läsbart utan verktyg, spårbart, AI-kompatibelt, långsiktigt.

---

## Mappstruktur

Varje förmåga är en egen `.md`-fil i en undermapp som motsvarar dess skikt:

```
/content/
  /ledning_styrning/        ← Direction — styrande förmågor
    cap-styr-001.md
    cap-styr-002.md
  /karnprocesser/           ← Core — värdeskapande förmågor
    cap-tjanst-001.md
    cap-inf-001.md
  /verksamhetsstod/         ← Enabling — stödjande förmågor
    cap-hr-001.md
    cap-it-001.md
```

> Layer-mappnamnen är konfigurerbart i `config/taxonomy.php`. Kontrollera mot
> den faktiska installationen — standardvärdena ovan gäller för ett nytt repo.

---

## Filformat

En fil per förmåga. Filnamn: `cap-[area]-[nnn].md` med sekventiella nummer per area.

```markdown
---
id: cap-hr-001
name: Löneadministration
layer: verksamhetsstod
area: HR
level: 2
type: verksamhetsformaga
description: Hanterar löneprocessen från registrering till utbetalning och rapportering.
owner: HR-chef
status: aktiv
maturity: 3
criticality: 4
tags: [hr, lön, administration]
updated: 2025-05-20
---

# Löneadministration

Förmågan att planera, genomföra och följa upp löneprocessen för samtliga medarbetare,
inklusive rapportering till Skatteverket och arbetsgivaravgifter.

## Vad förmågan omfattar
- Löneberedning och utbetalning
- Skatte- och arbetsgivaravgiftsrapportering
- Lönerevision och förhandlingsstöd

## Kopplingar
Beroende av cap-hr-002 (Kompetensförsörjning) för kompetensdataunderlag.
Se även cap-ekon-001 (Ekonomistyrning) för budgetintegration.
```

---

## YAML-fältspecifikation

| Fält | Typ | Obligatoriskt | Beskrivning |
|------|-----|---------------|-------------|
| `id` | `cap-[area]-[nnn]` | **Ja** | Unikt ID — används för auto-linking i appen |
| `name` | sträng | **Ja** | Förmågans namn (substantiv) |
| `layer` | enum | **Ja** | Skikt — se layer-värden nedan |
| `area` | sträng | **Ja** | Domän/område, t.ex. `HR`, `IT`, `Ekonomi` |
| `level` | int 1–3 | Nej | Hierarkinivå (L1=strategisk, L2=taktisk, L3=operativ) |
| `type` | sträng | Nej | `verksamhetsformaga`, `tekniskformaga`, `styrningsformaga` |
| `description` | sträng | **Ja** | Kort beskrivning, 1 mening, visas i listan |
| `owner` | sträng | Nej | Ansvarig roll eller funktion |
| `status` | enum | Nej | `aktiv`, `planerad`, `utgaende` |
| `maturity` | int 1–5 | **Ja** | Mognadsnivå — styr kantfärg i viewer (se nedan) |
| `criticality` | int 1–5 | Nej | Kritikalitet — alternativ heat-visualisering |
| `tags` | YAML-lista | Nej | Fritext-taggar för filtrering |
| `updated` | YYYY-MM-DD | Nej | Senast uppdaterad |

**Minimikrav per fil:** `id` + `name` + `layer` + `area` + `description` + `maturity`

---

## Enum-värden

### `layer` → mapp och skikt
| Värde | Mapp | EA-skikt |
|-------|------|----------|
| `ledning_styrning` | `/content/ledning_styrning/` | Direction |
| `karnprocesser` | `/content/karnprocesser/` | Core |
| `verksamhetsstod` | `/content/verksamhetsstod/` | Enabling |

### `maturity` → kantfärg i viewer
| Värde | CMMI-nivå | Kantfärg | Innebörd |
|-------|-----------|----------|----------|
| `1` | Initial | 🟥 Röd | Ad hoc, ostrukturerat, personberoende |
| `2` | Repeatable | 🟥 Röd | Vissa rutiner, inte dokumenterat |
| `3` | Defined | 🟨 Gul | Dokumenterade processer, standarder följs |
| `4` | Managed | 🟩 Grön | Mäts och följs upp kvantifierat |
| `5` | Optimizing | 🟩 Grön | Kontinuerlig förbättring, innovation |

### `criticality` → alternativ heat (konfigurerbart i `config/view.php`)
| Värde | Innebörd |
|-------|----------|
| `1` | Låg påverkan på verksamheten |
| `2` | Måttlig påverkan |
| `3` | Viktig för normal drift |
| `4` | Kritisk för verksamheten |
| `5` | Avgörande för överlevnad |

---

## ID-konvention

```
cap-[area]-[nnn]

cap-hr-001     → HR-domän, förmåga 1
cap-hr-002     → HR-domän, förmåga 2
cap-it-001     → IT-domän, förmåga 1
cap-styr-001   → Styrning/Direction, förmåga 1
cap-inf-001    → Infrastruktur, förmåga 1
cap-tjanst-001 → Tjänster/Core, förmåga 1
cap-ekon-001   → Ekonomi, förmåga 1
```

**Auto-linking:** Appen skapar automatiskt klickbara länkar när `cap-xxx-nnn` förekommer
i Markdown-texten. Använd detta för att koppla relaterade förmågor i brödtexten.

---

## Genereringsregler

När användaren ber om export till capability-map-app:

1. Producera **en fil per förmåga** — aldrig alla i ett block
2. Visa filnamn och mappplacering tydligt över varje fil
3. Inkludera alltid minimifälten: `id`, `name`, `layer`, `area`, `description`, `maturity`
4. Lägg `criticality` om affärsvärde är känt (hög → 4–5, medium → 3, låg → 1–2)
5. Skriv ett kort Markdown-innehållsavsnitt under frontmattern med minst:
   - Ingress (1–2 meningar som förklarar förmågan mer utförligt)
   - `## Vad förmågan omfattar` med 3–5 punkter
   - `## Kopplingar` med `cap-xxx-nnn`-referenser där relevant
6. Håll `description` i frontmattern till **en mening** — det är det som visas i kortlistan
7. Placera filen i rätt mapp utifrån `layer`-värdet

---

## Exempel — komplett filuppsättning

### `/content/ledning_styrning/cap-styr-001.md`
```markdown
---
id: cap-styr-001
name: Verksamhetsstyrning
layer: ledning_styrning
area: Styrning
level: 1
type: styrningsformaga
description: Styr och följer upp verksamhetens mål, prioriteringar och resurser.
owner: Verksamhetschef
status: aktiv
maturity: 4
criticality: 5
tags: [styrning, mål, uppföljning]
updated: 2025-05-20
---

# Verksamhetsstyrning

Förmågan att definiera, kommunicera och följa upp verksamhetsmål och strategiska
prioriteringar, samt säkerställa att resurser allokeras i linje med organisationens uppdrag.

## Vad förmågan omfattar
- Målformulering och strategisk planering
- Budget- och resursallokering
- Uppföljning och rapportering mot mål
- Portföljstyrning av initiativ och projekt

## Kopplingar
Styr prioriteringarna för cap-styr-002 (Arkitekturstyrning) och
cap-styr-003 (Kvalitets- och säkerhetsstyrning).
```

### `/content/karnprocesser/cap-tjanst-001.md`
```markdown
---
id: cap-tjanst-001
name: Tjänsteproduktion
layer: karnprocesser
area: Tjänster
level: 1
type: verksamhetsformaga
description: Levererar gemensamma IT-tjänster med definierad kvalitet och tillgänglighet.
owner: Tjänsteansvarig
status: aktiv
maturity: 4
criticality: 5
tags: [tjänster, leverans, sla]
updated: 2025-05-20
---

# Tjänsteproduktion

Förmågan att producera och leverera gemensamma IT-tjänster till förvaltningar och
verksamheter inom organisationen, enligt avtalade servicenivåer och kvalitetskrav.

## Vad förmågan omfattar
- Tjänstedesign och tjänstekatalog
- Leverans och driftsättning av tjänster
- SLA-uppföljning och kvalitetsmätning
- Tjänsteutveckling och livscykelhantering

## Kopplingar
Realiseras med stöd av cap-inf-001 (Infrastrukturförvaltning) och
cap-tjanst-002 (Ärendehantering) för incidenthantering.
```

### `/content/verksamhetsstod/cap-hr-001.md`
```markdown
---
id: cap-hr-001
name: Kompetensförsörjning
layer: verksamhetsstod
area: HR
level: 1
type: verksamhetsformaga
description: Säkerställer att organisationen har rätt kompetens för att fullgöra sitt uppdrag.
owner: HR-chef
status: aktiv
maturity: 3
criticality: 3
tags: [hr, kompetens, rekrytering]
updated: 2025-05-20
---

# Kompetensförsörjning

Förmågan att identifiera kompetensbehov, rekrytera, onboarda och utveckla medarbetare
så att organisationen kontinuerligt har den kompetens som krävs för att leverera sina tjänster.

## Vad förmågan omfattar
- Kompetensbehovsanalys och planering
- Rekrytering och urval
- Onboarding och introduktion
- Kompetensutveckling och utbildning
- Offboarding och kunskapsöverföring

## Kopplingar
Levererar kompetensdataunderlag till cap-hr-002 (Löneadministration).
Stödjer cap-tjanst-001 (Tjänsteproduktion) med rätt bemanning.
```
