<?php require __DIR__ . '/../app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="sv" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Practices: Förmågekartor (Capability Maps)</title>
    <link rel="icon" href="<?= h(base_path('assets/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= h(base_path('assets/favicon.png')) ?>" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        darkMode: 'class',
        theme: {
          extend: {
            colors: {
              inera: { blue:'#005595', dark:'#003e6d', light:'#e6f0f8' }
            }
          }
        }
      }
    </script>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        h2 { margin-top: 2rem; }
        .tip-box { border-left: 4px solid #3b82f6; background: #eff6ff; }
        .dark .tip-box { background: rgba(59, 130, 246, 0.1); border-left-color: #60a5fa; }
        .warning-box { border-left: 4px solid #f59e0b; background: #fffbeb; }
        .dark .warning-box { background: rgba(245, 158, 11, 0.1); border-left-color: #fbbf24; }
        a.resource-link { transition: all 0.2s; }
        a.resource-link:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    </style>
    <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
    <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body class="bg-slate-50 dark:bg-neutral-950 text-gray-800 dark:text-neutral-100 leading-relaxed">

    <header class="bg-slate-900 dark:bg-neutral-900 text-white py-10 border-b border-slate-800 dark:border-neutral-800">
        <div class="max-w-4xl mx-auto px-6">
            <div class="flex items-center justify-between mb-6">
              <a href="<?= h(base_path('view/index.php')) ?>" class="inline-flex items-center gap-2 text-sm text-slate-300 dark:text-neutral-400 hover:text-white dark:hover:text-neutral-200">
                ← Tillbaka till kartan
              </a>
              <button class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-slate-700 dark:border-neutral-700 bg-slate-800 dark:bg-neutral-800 hover:bg-slate-700 dark:hover:bg-neutral-700 transition"
                      type="button" data-theme-toggle aria-label="Växla tema">🌓</button>
            </div>
            <p class="text-blue-400 dark:text-blue-500 font-bold tracking-widest uppercase text-xs mb-2">Enterprise Architecture</p>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Designa en bra Förmågekarta</h1>
            <p class="text-slate-300 dark:text-neutral-300 text-lg max-w-2xl">
                En guide för att skapa stabila, tydliga och värdeskapande Capability Maps baserat på TOGAF och svenska offentliga standarder (Inera).
            </p>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-12 space-y-12">

        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">1. Vad utgör en "bra" karta?</h2>
            <p class="mb-4 text-gray-700 dark:text-neutral-300">
                En förmågekarta är inte ett organisationsschema och inte en processkarta. Den beskriver <strong>VAD</strong> verksamheten gör, inte <strong>HUR</strong> det görs eller <strong>VEM</strong> som gör det. En bra karta är stabil över tid, även om organisationen omorganiseras.
            </p>

            <div class="grid md:grid-cols-2 gap-6 mt-6">
                <div class="bg-white dark:bg-neutral-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800">
                    <h3 class="font-bold text-green-700 dark:text-green-500 mb-2">✅ Gör så här</h3>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li><strong>Substantiv-baserat:</strong> Använd "Rekrytering" istället för "Rekrytera". Det beskriver ett objekt/koncept.</li>
                        <li><strong>MECE:</strong> Förmågor ska vara <em>Mutually Exclusive, Collectively Exhaustive</em>. Inga överlappningar.</li>
                        <li><strong>Abstraktion:</strong> Håll samma detaljnivå inom samma lager (L1, L2).</li>
                        <li><strong>Affärsspråk:</strong> Använd begrepp som verksamheten förstår, inte IT-termer.</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-neutral-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800">
                    <h3 class="font-bold text-red-600 dark:text-red-500 mb-2">❌ Undvik detta</h3>
                    <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li><strong>Systemnamn:</strong> "SAP" är ingen förmåga. "Ekonomistyrning" är.</li>
                        <li><strong>Organisationsenheter:</strong> Avdelningar byter namn, förmågor består.</li>
                        <li><strong>Processverb:</strong> Undvik att beskriva flöden (steg 1, steg 2).</li>
                        <li><strong>Blandade nivåer:</strong> Blanda inte strategiska mål med operativa funktioner.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">2. Struktur & Visuell Modell</h2>
            <p class="mb-6 text-gray-700 dark:text-neutral-300">
                Enligt best practice (inklusive Ineras referensarkitektur) bör kartan delas in i tre distinkta skikt (Stratification). Detta hjälper intressenter att snabbt orientera sig.
            </p>

            <div class="border-2 border-dashed border-gray-300 dark:border-neutral-700 rounded-xl p-4 bg-gray-100 dark:bg-neutral-900 flex flex-col gap-4 text-center font-bold text-sm text-gray-500 dark:text-neutral-400">

                <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 p-4 rounded text-indigo-800 dark:text-indigo-300">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-indigo-400 dark:text-indigo-500">Strategiskt Skikt (Direction)</span>
                    STYRANDE VERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">Strategi, Arkitektur, Policy, Kvalitet</p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 p-8 rounded text-blue-900 dark:text-blue-300 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-blue-400 dark:text-blue-500">Värdeskapande Skikt (Core)</span>
                    KÄRNVERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">Det som är organisationens "raison d'etre" (ex. Vård, Skola, Tillverkning)</p>
                </div>

                <div class="bg-gray-200 dark:bg-neutral-800 border border-gray-300 dark:border-neutral-700 p-4 rounded text-gray-700 dark:text-neutral-300">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-gray-400 dark:text-neutral-500">Stödjande Skikt (Enabling)</span>
                    STÖDJANDE VERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">HR, IT, Ekonomi, Fastighet</p>
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">3. Att beskriva en förmåga</h2>
            <p class="mb-4 text-gray-700 dark:text-neutral-300">
                Enbart en "box" räcker inte. För att förmågan ska vara användbar i din applikation och för arkitekturell analys bör följande metadata finnas definierad (din Markdown Frontmatter).
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-neutral-900 border-b border-slate-200 dark:border-neutral-800 text-slate-700 dark:text-neutral-300">
                            <th class="p-3 font-semibold">Attribut</th>
                            <th class="p-3 font-semibold">Beskrivning</th>
                            <th class="p-3 font-semibold">Exempel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-800 bg-white dark:bg-neutral-950">
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Mognad (Maturity)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hur väl fungerar förmågan idag? (Ofta 1-5 skala)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">3 (Definierad)</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Affärsvärde</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hur kritisk är denna förmåga för strategin?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hög / Kritisk</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Personer (People)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka roller eller kompetenser krävs?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Läkare, Handläggare</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Process</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka processer realiserar denna förmåga?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Remissflöde</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Teknik (Technology)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka applikationer stöttar förmågan?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Cambio Cosmic, Visma</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-slate-50 dark:bg-neutral-900/50 p-6 rounded-xl border border-slate-200 dark:border-neutral-800">
            <h2 class="text-xl font-bold text-slate-900 dark:text-neutral-100 mb-4">Referenser & Läsning</h2>
            <div class="grid md:grid-cols-2 gap-4">

                <a href="https://www.inera.se/arkitektur/" target="_blank" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-blue-500 dark:hover:border-blue-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 text-xs font-bold px-2 py-1 rounded">Sverige</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-blue-700 dark:group-hover:text-blue-400">Inera Arkitektur</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Nationell referensarkitektur för vård och omsorg. Innehåller bra exempel på förmågekartor för offentlig sektor.</p>
                </a>

                <a href="https://pubs.opengroup.org/togaf-standard/business-architecture/business-capabilities.html" target="_blank" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-purple-500 dark:hover:border-purple-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 text-xs font-bold px-2 py-1 rounded">Global Standard</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-purple-700 dark:group-hover:text-purple-400">TOGAF Series Guide</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">The Open Groups officiella guide för Business Capabilities. Den "bibel" som de flesta enterprise arkitekter följer.</p>
                </a>

                <a href="https://www.businessarchitectureguild.org/" target="_blank" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-green-500 dark:hover:border-green-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-green-100 dark:bg-green-950 text-green-800 dark:text-green-300 text-xs font-bold px-2 py-1 rounded">Deep Dive</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-green-700 dark:group-hover:text-green-400">BIZBOK® Guide</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Business Architecture Body of Knowledge. Går djupt in på hur man mappar capabilities mot värdeströmmar.</p>
                </a>

                 <a href="https://www.apqc.org/" target="_blank" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-orange-500 dark:hover:border-orange-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-orange-100 dark:bg-orange-950 text-orange-800 dark:text-orange-300 text-xs font-bold px-2 py-1 rounded">Ramverk</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-orange-700 dark:group-hover:text-orange-400">APQC PCF</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Process Classification Framework. Bra inspiration om man har svårt att hitta namn på förmågor (även om det är processfokus).</p>
                </a>

            </div>
        </section>

    </main>

    <footer class="bg-slate-900 dark:bg-neutral-950 text-slate-400 dark:text-neutral-500 py-8 text-center text-sm border-t border-slate-800 dark:border-neutral-900">
        <p>&copy; 2025 Capability Map Guide.</p>
    </footer>

</body>
</html>