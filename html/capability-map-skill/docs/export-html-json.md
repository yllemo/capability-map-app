# Export: JSON för Förmågekarta HTML-app

Exportformat för den fristående HTML-appen med drag-and-drop, PNG-export och Markdown-export.
Appen kräver ingen installation — öppna HTML-filen direkt i webbläsaren.

Läs in detta dokument när användaren ber om JSON-export kompatibelt med HTML-appen,
nämner "importera till HTML-appen", "drag-and-drop-verktyget", eller "JSON för förmågekartan".

---

## Applikation

HTML-appen är en fristående SPA (single-page application) utan backend eller databas.
Förmågor lagras i minnet under sessionen och kan exporteras/importeras som JSON.

**Funktioner:**
- Drag-and-drop mellan skikt (Direction / Core / Enabling)
- PNG-export av hela kartan
- Markdown-export för dokumentation
- JSON-import och export för backup och delning
- Delningslänk (hela kartan base64-kodad i URL:en som `?data=…`)
- Redigerbart organisationsnamn **och** kartbeskrivning i meta-baren
- Valfria länkar på förmågekort (klickbar globikon nere till höger vid hover)

---

## JSON-filstruktur

```json
{
  "exportDate": "2026-06-17T10:00:00.000Z",
  "version": "1.0",
  "organization": "Intraservice, Göteborgs Stad",
  "description": "Förmågekarta för gemensamma tjänster (HR, Ekonomi, KLoS).",
  "capabilities": [
    {
      "id": 1,
      "title": "Ärendehantering",
      "description": "Förmågan att ta emot, handlägga och avsluta ärenden från medborgare och verksamheter.",
      "url": "https://intranat.goteborg.se/arendehantering",
      "status": "defined",
      "layer": "core"
    },
    {
      "id": 2,
      "title": "Informationssäkerhet",
      "description": "Förmåga att skydda information och system mot obehörig åtkomst, enligt NIS2.",
      "status": "managed",
      "layer": "strategic"
    },
    {
      "id": 3,
      "title": "HR-hantering",
      "description": "Förmåga att rekrytera, utveckla och administrera medarbetare.",
      "status": "defined",
      "layer": "support"
    }
  ]
}
```

> Notera att `description` (toppnivå) och `url` (per förmåga) är **valfria**. Filen fungerar lika bra med eller utan dem — fält som saknas hoppas över utan fel.

---

## Fältspecifikation

### Toppnivå
| Fält | Typ | Obligatoriskt | Beskrivning |
|------|-----|---------------|-------------|
| `exportDate` | ISO 8601 | Nej | Exporttidpunkt — generera aktuellt datum |
| `version` | sträng | Nej | Alltid `"1.0"` |
| `organization` | sträng | Nej | Organisationsnamn (visas till vänster i meta-baren, redigerbart) |
| `description` | sträng | Nej | **Kartans** beskrivning — visas centrerad i meta-baren i tunnare text, redigerbar. Skiljer sig från förmågornas `description` |
| `capabilities` | array | Ja | Lista med förmågeobjekt |

### Per förmåga
| Fält | Typ | Obligatoriskt | Beskrivning |
|------|-----|---------------|-------------|
| `id` | int | Ja | Sekventiellt heltal från 1 — appen räknar om vid import |
| `title` | sträng | Ja | Förmågans namn, max ~50 tecken för att passa i kortet |
| `description` | sträng | Ja | Vad förmågan möjliggör, 1–3 meningar |
| `url` | sträng | Nej | Länk kopplad till förmågan. Visas som klickbar globikon nere till höger på kortet (endast vid hover) och öppnas i ny flik. Protokoll antas vara `https://` om det utelämnas |
| `status` | enum | Ja | Mognadsnivå — se tabell nedan |
| `layer` | enum | Ja | Skikt — se tabell nedan |

> Förmågor med saknade **obligatoriska** fält ignoreras tyst vid import. Valfria fält (`url`, kartans `description`) påverkar inte inläsningen om de saknas.

---

## Enum-värden

### `layer` → skikt
| Värde | Skikt | Beskrivning |
|-------|-------|-------------|
| `"strategic"` | Direction | Styrande förmågor: strategi, arkitektur, policy, säkerhet |
| `"core"` | Core | Kärnverksamhet: det som skapar det primära värdet |
| `"support"` | Enabling | Stödjande förmågor: HR, IT, ekonomi, juridik |

### `status` → mognad
| Värde | Svensk label | CMMI-nivå | Kortfärg |
|-------|-------------|-----------|----------|
| `"initial"` | Initial | 1 | 🔴 röd vänsterkant |
| `"developing"` | Under utveckling | 2 | 🟡 gul vänsterkant |
| `"defined"` | Definierad | 3 | 🔵 blå vänsterkant |
| `"managed"` | Hanterad | 4 | 🟢 grön vänsterkant |
| `"optimized"` | Optimerad | 5 | 🟣 indigo vänsterkant |

---

## Genereringsregler

När användaren ber om JSON-export kompatibelt med HTML-appen:

1. Producera **hela JSON-blocket** inkl. `exportDate`, `version`, `organization`, `capabilities`
2. Sätt `exportDate` till aktuellt datum i ISO 8601-format
3. Inkludera `description` på toppnivå om det finns en övergripande beskrivning av kartan — annars utelämna fältet
4. Numrera `id` sekventiellt från 1
5. Håll `title` kort — det är det som syns i kortets header
6. Skriv förmågans `description` som en komplett mening som förklarar förmågan, inte ett namn
7. Lägg endast till `url` på en förmåga när en relevant länk finns — utelämna fältet helt annars
8. Välj `status` utifrån känd eller antagen mognadsnivå — default `"defined"` om okänt
9. Mappa skikt konsekvent: Direction → `"strategic"`, Core → `"core"`, Enabling → `"support"`

---

## Exempel — komplett export

```json
{
  "exportDate": "2026-06-17T10:00:00.000Z",
  "version": "1.0",
  "organization": "Intraservice, Göteborgs Stad",
  "description": "Förmågekarta för gemensamma tjänster — styrning, kärnleverans och stöd.",
  "capabilities": [
    {
      "id": 1,
      "title": "Verksamhetsstyrning",
      "description": "Förmåga att styra och följa upp verksamhetens mål, prioriteringar och resurser.",
      "status": "managed",
      "layer": "strategic"
    },
    {
      "id": 2,
      "title": "Arkitekturstyrning",
      "description": "Förmåga att definiera, förvalta och säkerställa efterlevnad av IT- och verksamhetsarkitektur.",
      "url": "https://intranat.goteborg.se/arkitektur",
      "status": "defined",
      "layer": "strategic"
    },
    {
      "id": 3,
      "title": "Informationssäkerhet",
      "description": "Förmåga att skydda information och system mot obehörig åtkomst och intrång, i enlighet med NIS2.",
      "status": "managed",
      "layer": "strategic"
    },
    {
      "id": 4,
      "title": "Tjänsteproduktion",
      "description": "Förmåga att leverera gemensamma IT-tjänster med definierad kvalitet och tillgänglighet.",
      "status": "managed",
      "layer": "core"
    },
    {
      "id": 5,
      "title": "Ärendehantering",
      "description": "Förmåga att ta emot, prioritera, handlägga och avsluta ärenden och felanmälningar.",
      "status": "defined",
      "layer": "core"
    },
    {
      "id": 6,
      "title": "Informationsförsörjning",
      "description": "Förmåga att samla in, kvalitetssäkra och tillgängliggöra information och masterdata.",
      "status": "developing",
      "layer": "core"
    },
    {
      "id": 7,
      "title": "HR-hantering",
      "description": "Förmåga att rekrytera, utveckla och administrera medarbetare och kompetens.",
      "status": "defined",
      "layer": "support"
    },
    {
      "id": 8,
      "title": "Ekonomihantering",
      "description": "Förmåga att planera, följa upp och redovisa ekonomi och budget.",
      "status": "defined",
      "layer": "support"
    },
    {
      "id": 9,
      "title": "IT-förvaltning",
      "description": "Förmåga att förvalta, upphandla och livscykelhantera system och tekniska plattformar.",
      "url": "https://intranat.goteborg.se/it-forvaltning",
      "status": "managed",
      "layer": "support"
    }
  ]
}
```
