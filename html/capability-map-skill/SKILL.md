---
name: capability-map
description: Skapa förmågekartor (Capability Maps) och enskilda förmågor (Capabilities) enligt best practice från TOGAF, ArchiMate 3.2 och svenska offentliga sektorns standarder. Använd denna skill när användaren vill skapa, strukturera, namnge, klassificera eller analysera förmågor eller förmågekartor. Trigga alltid vid nyckelord som "förmåga", "förmågekarta", "capability", "capability map".
author: Henrik Yllemo
version: 1.0.1
tags: cap, capability, förmåga, kompetens
---

# Förmågekarta 

Styr hur AI skapar och strukturerar förmågor och förmågekartor (Capability Maps) enligt best practice. Källorna är TOGAF Series Guide to Business Capabilities (The Open Group), ArchiMate 3.2, BIZBOK® och svenska offentliga sektorns referensarkitektur (Inera).

För applikationsspecifika exportformat, se:
- `docs/export-html-json.md` — JSON-export för HTML-appen (drag-and-drop)
- `docs/export-cap-map-app.md` — Markdown/YAML-export för capability-map-app (PHP/Git)

---

## Grunddefinition

En **förmåga (capability)** beskriver **VAD** en organisation kan göra för att skapa värde — oberoende av **HUR** det görs, **VEM** som gör det eller **VILKET SYSTEM** som används.

> "Vi har förmågan att..." — om meningen är meningsfull, är det troligen en förmåga.

En förmåga är:
- **Stabil över tid** — bestående även vid omorganisation
- **Teknologioberoende** — inte bunden till ett specifikt system
- **Unik och avgränsad** — ingen överlappning med andra förmågor
- **Affärsorienterad** — formulerad i verksamhetsspråk, inte IT-jargong

---

## Steg 1: Klargör kontext

Innan du skapar förmågor, identifiera:
1. **Typ av organisation** — kommun, region, myndighet, sjukhus, privat företag?
2. **Syfte** — strategisk analys, gap-analys, IT-portföljhantering, TOGAF ADM, ArchiMate-modellering?
3. **Omfång** — hela organisationen (L1) eller en specifik domän (L2/L3)?
4. **Målgrupp** — ledning, arkitekter, verksamhetsutvecklare?
5. **Önskat format** — Markdown-lista, JSON, HTML-tabell, ArchiMate, fritextbeskrivning?

Om användaren inte specificerat dessa, anta rimliga defaults baserat på kontext och fråga vid behov om ett.

---

## Steg 2: Namngivning

### Regler för förmågenamn
| Regel | Rätt | Fel |
|-------|------|-----|
| **Substantiv, inte verb** | "Rekrytering" | "Rekrytera medarbetare" |
| **Affärsspråk, inte IT** | "Ekonomistyrning" | "SAP-hantering" |
| **Inte organisationsenheter** | "Kompetensutveckling" | "HR-avdelningens arbete" |
| **Inte processer** | "Ärendehantering" | "Registrera → Tilldela → Lösa" |
| **Tidsresistent** | "Fastighetsdrift" | "Byggnation av Hus B 2024" |
| **Distinkt och avgränsat** | "Löneadministration" | "Lön och personal" (för brett) |

### Vanliga prefix-mönster (svenska offentlig sektor)
- `[Domän]hantering` → Ärendehantering, Informationshantering
- `[Objekt]styrning` → Kvalitetsstyrning, Ekonomistyrning
- `[Aktivitet]stöd` → Beslutsstöd, Verksamhetsstöd
- `[Objekt]förvaltning` → Fastighetsförvaltning, Systemförvaltning

---

## Steg 3: Stratifiering (tre skikt)

Alla förmågor klassificeras i ett av tre skikt:

```
┌─────────────────────────────────────────────┐
│  DIRECTION – Strategiskt skikt              │  ~10–15 %
│  Styr, beslutar, kvalitetssäkrar           │
│  Ex: Strategi, Arkitektur, Policy, Säkerhet│
├─────────────────────────────────────────────┤
│  CORE – Värdeskapande skikt                 │  ~50–60 %
│  Organisationens kärnuppdrag (raison d'être)│
│  Ex: Vård, Utbildning, Tillverkning        │  ← Hjärtat
├─────────────────────────────────────────────┤
│  ENABLING – Stödjande skikt                 │  ~30–40 %
│  Möjliggörare, ofta outsourcingbara         │
│  Ex: HR, IT, Ekonomi, Juridik, Fastighet   │
└─────────────────────────────────────────────┘
```

**Test för Core:** "Skulle organisationen upphöra att existera om vi inte hade denna förmåga?" — Om ja → Core. Om nej → Enabling eller Direction.

---

## Steg 4: Hierarki och MECE

### Hierarkiregler
- **L1 (strategisk):** 7–12 förmågor totalt, täcker hela organisationen
- **L2 (taktisk):** 3–8 barn per L1-förmåga
- **L3 (operativ):** Sällan nödvändigt — max om specialistbehov föreligger
- **Max 2–3 nivåer** — djupare blir oöverskådligt

### MECE-principen (Mutually Exclusive, Collectively Exhaustive)
Förmågor på samma nivå ska vara:
- **ME – Ömsesidigt exklusiva:** Ingen överlappning. "Kundkommunikation" och "E-post till kunder" bryter ME.
- **CE – Gemensamt uttömmande:** Inga vita fläckar. Allt verksamheten gör ska rymmas någonstans.

**Kontroll:** Kan du placera varje verksamhetsaktivitet i exakt en förmåga? Om inte → MECE bryts.

---

## Steg 5: Metadata per förmåga

En väl definierad förmåga innehåller följande attribut (alla behövs inte alltid):

| Attribut | Typ | Beskrivning |
|----------|-----|-------------|
| `namn` | Text | Förmågans namn (substantiv) |
| `beskrivning` | Text | Vad förmågan möjliggör (1–3 meningar) |
| `skikt` | Enum | Direction / Core / Enabling |
| `nivå` | Enum | L1 / L2 / L3 |
| `förälder` | Ref | Överordnad förmåga (L2+) |
| `mognad_aktuell` | 1–5 | CMMI-skala: 1=Initial, 2=Managed, 3=Defined, 4=Quantitatively Managed, 5=Optimizing |
| `mognad_mål` | 1–5 | Önskad mognadsnivå |
| `mognadsgap` | Beräknat | mål − aktuell (positivt = underinvesterat) |
| `affärsvärde` | Låg/Medium/Hög/Kritisk | Strategisk vikt |
| `roller` | Lista | Vilka roller/kompetenser krävs? |
| `processer` | Lista | Vilka processer realiserar förmågan? |
| `system` | Lista | Stödjande applikationer/system |
| `information` | Lista | Informationsobjekt som används/produceras |
| `ansvarig` | Text | Ägande funktion/roll |
| `status` | Enum | Befintlig / Planerad / Utgående |

**Minimikrav för en giltig förmåga:** namn + beskrivning + skikt + nivå.

---

## Steg 6: CMMI-mognadsskala

| Nivå | Namn | Beskrivning |
|------|------|-------------|
| 1 | Initial | Ad hoc, odefinierad, personberoende |
| 2 | Repeatable | Dokumenterad, repeterbar men reaktiv |
| 3 | Defined | Standardiserad process, proaktiv |
| 4 | Quantitatively Managed | Mätt och styrd med data |
| 5 | Optimizing | Kontinuerlig förbättring inbyggd |

---

## Steg 7: Heat Map-analys

Kombinera attribut för investeringsprioritering:

```
Hög mognadsgap + Högt affärsvärde  →  🔴 Kritisk investering (prioritera nu)
Lågt mognadsgap + Högt affärsvärde →  🟢 Styrka (bevara)
Hög mognadsgap + Lågt affärsvärde  →  🟡 Övervaka (låg prioritet)
Negativt mognadsgap                →  🔵 Övermogen (resurser kan omfördelas?)
```

**Tre vanliga heat map-dimensioner:**
1. **Teknisk fitness** — Är nuvarande arkitektur ändamålsenlig? (skalbarhet, säkerhet, modernitet)
2. **Affärsvärde** — Strategisk vikt, kundpåverkan, regulatorisk nödvändighet
3. **Transformationsberedskap** — Kan förmågan förändras med rimlig insats?

---

## Steg 8: Validering

Kontrollera varje förmåga mot dessa frågor:
- [ ] Är det ett substantiv (inte ett verb eller ett systemnamn)?
- [ ] Kan jag säga "Vi har förmågan att..."?
- [ ] Är den stabil även om organisationen omorganiseras?
- [ ] Är den MECE gentemot sina systrar på samma nivå?
- [ ] Är abstraktionsnivån konsekvent med övriga på samma nivå?
- [ ] Förstår en verksamhetsperson vad det innebär utan förklaring?

---

## Steg 9: Utdataformat

Välj format utifrån vad användaren behöver:

| Format | Används när | Detaljer |
|--------|-------------|----------|
| **Markdown-lista** | Dokumentation, presentation | Inline i svar |
| **JSON – HTML-app** | Import till drag-and-drop HTML-appen | Se `docs/export-html-json.md` |
| **Markdown/YAML** | capability-map-app (PHP/Git) | Se `docs/export-cap-map-app.md` |
| **ArchiMate** | EA-modellering | Se skill `archicode` |
| **HTML SPA** | Göteborgs Stad-verktyg | Se skill `goteborgs-stad-design` |

### Markdown-lista (standard för dokumentation)
```markdown
## Direction
- Strategistyrning
- Arkitekturstyrning
- Kvalitetsledning

## Core
- Ärendehantering
  - Ärendeintagning
  - Ärendehandläggning
  - Ärendeavslut
- Informationsförsörjning

## Enabling
- HR-hantering
- Ekonomihantering
- IT-förvaltning
```

---

## Vanliga misstag – snabbreferens

| Misstag | Symptom | Lösning |
|---------|---------|---------|
| Systemnamn som förmåga | "SAP", "Teams" | Ersätt med affärsförmågan förmågan stödjer |
| Org-enhet som förmåga | "HR-avdelningen" | Beskriv VAD de gör: "Kompetensförsörjning" |
| Allt är Core | Inget sticker ut strategiskt | Fråga: "Upphör vi om detta saknas?" |
| För många nivåer | L4, L5... | Max L3, helst L2 räcker |
| Överlappningar | "Kundkommunikation" + "Mejla kunder" | Slå ihop eller separera tydligt |
| PowerPoint-statisk karta | Snabbt inaktuell | Strukturerad data + dynamisk vy |

---

## Exempelkartor per organisationstyp

Se `references/exempelkartor.md` för fullständiga L1+L2-exempelkartor för:
- Kommun (Göteborgs Stad-profil)
- Sjukhus / Region
- Statlig myndighet
- Intraservice / Gemensamma Tjänster
- E-handel / Privat företag
- Tillverkningsföretag

---

## Källor

- **TOGAF® Series Guide: Business Capabilities** (The Open Group, G189) — primär referens
- **ArchiMate® 3.2 Specification** — Capability-elementet och Capability Map Viewpoint
- **BIZBOK® Guide** — Business Architecture Body of Knowledge, Business Architecture Guild
- **Inera Referensarkitektur** — Nationell referensarkitektur för vård och omsorg (inera.se/arkitektur)
- **APQC Process Classification Framework** — inspiration för namngivning
- **CMMI Institute** — mognadsskala 1–5



