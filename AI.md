# AI Guide: Skapa Capability Markdown Filer

Denna guide förklarar för AI-assistenter hur man skapar markdown filer som fungerar korrekt med Capability Map verktyget. Systemet använder Markdown filer med YAML frontmatter för att definiera förmågor (capabilities).

## 📋 Grundläggande Struktur

Varje capability markdown fil MÅSTE följa denna struktur:

```markdown
---
id: unique-id-here
name: Capability Name
layer: layer_name
area: Business Area
level: 1-3
type: capability_type
description: Short description
# ... additional metadata
---

# Capability Name

Detailed description of the capability goes here in the markdown body.

## Purpose
What this capability achieves...

## Key Activities
- Activity 1
- Activity 2
```

## 🏗️ YAML Frontmatter - Obligatoriska Fält

### `id` (sträng, OBLIGATORISK)
- **Syfte**: Unik identifierare för förmågan
- **Format**: Kebab-case, alfanumeriska tecken och bindestreck
- **Exempel**: `"cap-hr-recruitment"`, `"patient-care-001"`
- **Validering**: Måste vara unik inom hela systemet

### `name` (sträng, OBLIGATORISK)  
- **Syfte**: Läsbart namn som visas i UI
- **Riktlinjer**: 
  - Använd substantiv, INTE verb (rätt: "Rekrytering", fel: "Rekrytera")
  - Undvik systemnamn (rätt: "Ekonomistyrning", fel: "SAP")
  - Använd affärsspråk som verksamheten förstår
- **Exempel**: `"Rekrytering"`, `"Patientvård"`, `"Ekonomistyrning"`

### `layer` (sträng, OBLIGATORISK)
- **Syfte**: Vilket strategiskt skikt förmågan tillhör
- **Tillåtna värden**:
  - `"ledning_styrning"` - Ledning & styrning (Direction layer)
  - `"karnprocesser"` - Kärnprocesser (Core layer)  
  - `"verksamhetsstod"` - Verksamhetsstöd (Enabling layer)
- **Exempel**: `layer: karnprocesser`

### `level` (heltal, OBLIGATORISK)
- **Syfte**: Hierarkisk nivå i capability map (1-3)
- **Värden**: 
  - `1` - Strategisk nivå (få, breda förmågor)
  - `2` - Taktisk nivå (huvudsaklig detaljnivå)
  - `3` - Operativ nivå (detaljerad, för specialister)
- **Exempel**: `level: 2`

## 🔧 Metadata - Rekommenderade Fält

### `area` (sträng, REKOMMENDERAD)
- **Syfte**: Affärsområde eller domän
- **Exempel**: `"HR"`, `"IT"`, `"Vårdprocesser"`, `"Ekonomi"`

### `type` (sträng, REKOMMENDERAD)
- **Tillåtna värden**:
  - `"verksamhetsformaga"` - Verksamhetsförmåga
  - `"stodformaga"` - Stödförmåga
- **Exempel**: `type: verksamhetsformaga`

### `description` (sträng, REKOMMENDERAD)
- **Syfte**: Kort beskrivning av förmågan (1-2 meningar)
- **Exempel**: `"Förmågan att attrahera, rekrytera och onboarda ny personal enligt organisationens behov."`

## 📊 Utökade Metadata för Analys

### Mognad & Värdering
```yaml
maturity: 3          # Nuvarande mognadsnivå (1-5, CMMI-skala)
target_maturity: 4   # Målmognad
criticality: 3       # Kritikalitet för verksamheten (1-5)
business_value: "high"  # Affärsvärde: "critical", "high", "medium", "low"
```

### Ansvar & Organisation  
```yaml
owner: "HR-chef"           # Ansvarig funktion/roll
stakeholders: ["HR", "IT"] # Intressenter (array)
```

### Klassificering & Kategorisering
```yaml
tags: ["viktig", "extern", "digital"]  # Taggar för klassificering (array)
```

### Teknisk Information
```yaml
technologies: ["Visma", "SharePoint"]  # Stödjande system (array)
processes: ["Recruitment Process"]     # Relaterade processer (array)
```

### Status & Tidslinjer
```yaml
status: "active"        # "active", "planned", "deprecated"
updated: "2025-01-08"   # Senaste uppdatering (YYYY-MM-DD)
version: "1.0"          # Version av förmågedefinitionen
```

## 📂 Fil- och Mappstruktur

### Filplacering
Filer MÅSTE placeras i korrekt mapp baserat på `layer`:
- `content/ledning_styrning/` - För Direction layer
- `content/karnprocesser/` - För Core layer  
- `content/verksamhetsstod/` - För Enabling layer

### Filnamn
- **Format**: Kebab-case med `.md` extension
- **Rekommendation**: Baserat på capability name
- **Exempel**: 
  - Name: "Rekrytering" → Fil: `rekrytering.md`
  - Name: "IT-drift" → Fil: `it-drift.md`

## ✅ Fullständigt Exempel

```markdown
---
id: cap-hr-recruitment
name: Rekrytering
layer: verksamhetsstod
area: HR
level: 2
type: verksamhetsformaga
description: Förmågan att attrahera, rekrytera och onboarda ny personal enligt organisationens behov.
maturity: 3
target_maturity: 4
criticality: 4
business_value: high
owner: HR-chef
stakeholders: ["HR", "Verksamhetschefer", "IT"]
tags: ["kritisk", "personal", "extern"]
technologies: ["Visma HR", "LinkedIn Recruiter", "Teams"]
processes: ["Rekryteringsprocess", "Onboarding-process"]
status: active
updated: "2025-01-08"
version: "1.2"
---

# Rekrytering

Organisationens förmåga att systematiskt identifiera, attrahera, utvärdera och anställa kvalificerad personal som matchar verksamhetens behov och kultur.

## Syfte
Säkerställa att organisationen har rätt kompetens vid rätt tidpunkt för att uppnå sina strategiska mål.

## Centrala Aktiviteter
- **Behovsanalys**: Identifiera och definiera rekryteringsbehov
- **Attraktivitet**: Utveckla och kommunicera employer brand
- **Sourcing**: Hitta och attrahera potentiella kandidater  
- **Selektion**: Utvärdera och välja rätt kandidater
- **Onboarding**: Introducera nya medarbetare effektivt

## Viktiga Leveranser
- Bemanningsplan
- Kompetenskrav och rollbeskrivningar
- Rekryteringsannons och marknadsföring
- Strukturerad urvalsprocess
- Onboarding-program

## Framgångsfaktorer
- Tydliga kompetenskrav och rollbeskrivningar
- Attraktiv employer brand
- Effektiva rekryteringskanaler
- Strukturerad och rättvis urvalsprocess
- Systematisk onboarding

## Relaterade Förmågor
- Kompetensutveckling
- Performance Management  
- HR-administration
- Arbetsmiljö och hälsa
```

## 🚨 Viktiga Valideringsregler

### YAML Frontmatter Validering
1. **Syntax**: Måste vara korrekt YAML (använd YAML validator)
2. **Obligatoriska fält**: `id`, `name`, `layer`, `level` MÅSTE finnas
3. **Unika ID:n**: Varje `id` måste vara unikt i hela systemet
4. **Tillåtna värden**: `layer` och `type` måste matcha definierade värden

### Naming Conventions
1. **Substantiv**: Använd substantiv, inte verb
   - ✅ "Rekrytering", "Patientvård", "Ekonomistyrning"
   - ❌ "Rekrytera", "Vårda patienter", "Hantera ekonomi"

2. **Affärsspråk**: Använd termer som verksamheten förstår
   - ✅ "Ekonomistyrning", "Kompetensutveckling"  
   - ❌ "SAP", "HRIS-system"

3. **MECE-principen**: Förmågor på samma nivå ska vara:
   - **Mutually Exclusive**: Ingen överlappning
   - **Collectively Exhaustive**: Täcker allt

### Hierarki och Relationer
1. **Konsekvent abstraktion**: Håll samma detaljnivå inom varje level
2. **Logisk hierarki**: Level 1 (bred) → Level 2 (specifik) → Level 3 (detaljerad)
3. **Balanserade träd**: Undvik för många eller för få barn per förmåga

## 🔍 Common Pitfalls för AI

### ❌ Vanliga Fel
1. **Verb som namn**: "Hantera kunder" → "Kundhantering"
2. **Systemnamn**: "SharePoint" → "Kunskapshantering"  
3. **Processflöden**: "Steg 1, Steg 2" → "Affärsområde"
4. **Blandade abstraktionsnivåer**: Level 1 och Level 3 termer på samma nivå

### ✅ Best Practices  
1. **Substantivform**: Alltid substantiv som beskriver en förmåga
2. **Stabila namn**: Namn som överlever organisationsförändringar
3. **Affärsfokus**: Beskriv VAD, inte HUR eller VEM
4. **Konsekvent metadata**: Använd samma fält och format genomgående

## 🛠️ Verktyg för Validering

Innan du skapar filer, kontrollera:
1. **YAML Syntax**: Använd YAML linter
2. **Unika ID:n**: Kontrollera mot befintliga filer
3. **Tillåtna värden**: Matcha mot taxonomy.php konfiguration
4. **Filplacering**: Rätt mapp baserat på layer

Denna guide säkerställer att AI-genererade capability filer fungerar korrekt med verktyget och följer etablerade best practices för enterprise architecture.