<?php require __DIR__ . '/../app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="sv" class="antialiased">
<head>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Practices: Förmågekartor (Capability Maps)</title>
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
        .success-box { border-left: 4px solid #10b981; background: #ecfdf5; }
        .dark .success-box { background: rgba(16, 185, 129, 0.1); border-left-color: #34d399; }
        a.resource-link { transition: all 0.2s; }
        a.resource-link:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .example-card { transition: all 0.3s ease; }
        .example-card:hover { box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
    </style>
    <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
    <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body class="bg-slate-50 dark:bg-neutral-950 text-gray-800 dark:text-neutral-100 leading-relaxed">

    <header class="bg-slate-900 dark:bg-neutral-900 text-white py-10 border-b border-slate-800 dark:border-neutral-800">
        <div class="max-w-5xl mx-auto px-6">
            <div class="flex items-center justify-between mb-6">
              <a href="<?= h(base_path('view/index.php')) ?>" class="inline-flex items-center gap-2 text-sm text-slate-300 dark:text-neutral-400 hover:text-white dark:hover:text-neutral-200">
                ← Tillbaka till kartan
              </a>
              <button class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-slate-700 dark:border-neutral-700 bg-slate-800 dark:bg-neutral-800 hover:bg-slate-700 dark:hover:bg-neutral-700 transition"
                      type="button" data-theme-toggle aria-label="Växla tema">🌓</button>
            </div>
            <p class="text-blue-400 dark:text-blue-500 font-bold tracking-widest uppercase text-xs mb-2">Enterprise Architecture</p>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Designa en bra Förmågekarta</h1>
            <p class="text-slate-300 dark:text-neutral-300 text-lg max-w-3xl">
                En guide för att skapa stabila, tydliga och värdeskapande Capability Maps baserat på TOGAF och svenska offentliga standarder (Inera).
            </p>
            <div class="mt-6">
                <a href="<?= h(base_path('view/capability-map-wizard.php')) ?>" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg hover:shadow-xl">
                    🧭 Starta Steg-för-steg Guide
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-12 space-y-12">

        <!-- Quick Start -->
        <section class="success-box p-6 rounded-lg">
            <h2 class="text-xl font-bold text-green-900 dark:text-green-300 mb-3">🚀 Snabbstart</h2>
            <p class="text-gray-700 dark:text-neutral-300 mb-4">
                Ny på förmågekartor? Följ dessa tre steg:
            </p>
            <ol class="list-decimal pl-6 space-y-2 text-gray-700 dark:text-neutral-300">
                <li>Läs <a href="#vad-ar-formaga" class="text-blue-600 dark:text-blue-400 underline">Vad är en förmåga?</a> nedan</li>
                <li>Granska <a href="#exempel" class="text-blue-600 dark:text-blue-400 underline">praktiska exempel</a></li>
                <li>Använd <a href="<?= h(base_path('view/capability-map-wizard.php')) ?>" class="text-blue-600 dark:text-blue-400 underline font-semibold">steg-för-steg guiden</a> för att skapa din första förmåga</li>
            </ol>
        </section>

        <!-- Vad är en förmåga -->
        <section id="vad-ar-formaga">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b-2 border-blue-500 pb-2">Vad är en förmåga?</h2>
            
            <div class="bg-white dark:bg-neutral-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800 mb-6">
                <p class="text-lg text-gray-700 dark:text-neutral-300 mb-4">
                    En <strong>förmåga (capability)</strong> beskriver <em>vad</em> en organisation kan göra för att skapa värde, oberoende av <em>hur</em> det görs, <em>vem</em> som gör det eller <em>vilket system</em> som används.
                </p>
                <div class="bg-blue-50 dark:bg-blue-950/30 p-4 rounded border border-blue-200 dark:border-blue-800">
                    <p class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Exempel: "Hantera kundärenden"</p>
                    <ul class="text-sm text-gray-700 dark:text-neutral-300 space-y-1">
                        <li>✅ <strong>Förmåga:</strong> Hantera kundärenden (VAD)</li>
                        <li>❌ <strong>Inte process:</strong> "Registrera → Tilldela → Lös → Stäng" (HUR)</li>
                        <li>❌ <strong>Inte organisation:</strong> "Kundtjänstavdelningen" (VEM)</li>
                        <li>❌ <strong>Inte system:</strong> "Salesforce" (MED VAD)</li>
                    </ul>
                </div>
            </div>

            <div class="tip-box p-5 rounded-lg">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2">💡 Minnesregel</h3>
                <p class="text-gray-700 dark:text-neutral-300">
                    Om du kan säga "Vi har förmågan att..." så har du troligen rätt abstraktionsnivå. 
                    Exempel: "Vi har förmågan att hantera kundärenden" ✅<br>
                    Inte: "Vi har förmågan att Salesforce" ❌
                </p>
            </div>
        </section>

        <!-- Grundprinciper -->
        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">1. Vad utgör en "bra" karta?</h2>
            <p class="mb-4 text-gray-700 dark:text-neutral-300">
                En förmågekarta är inte ett organisationsschema och inte en processkarta. Den beskriver <strong>VAD</strong> verksamheten gör, inte <strong>HUR</strong> det görs eller <strong>VEM</strong> som gör det. En bra karta är stabil över tid, även om organisationen omorganiseras.
            </p>

            <div class="grid md:grid-cols-2 gap-6 mt-6">
                <div class="bg-white dark:bg-neutral-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800">
                    <h3 class="font-bold text-green-700 dark:text-green-500 mb-4 text-lg">✅ Gör så här</h3>
                    <ul class="space-y-3 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex gap-3">
                            <span class="text-green-600 dark:text-green-500 font-bold shrink-0">→</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Substantiv-baserat:</strong> Använd "Rekrytering" istället för "Rekrytera". Det beskriver ett objekt/koncept.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-green-600 dark:text-green-500 font-bold shrink-0">→</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">MECE:</strong> Förmågor ska vara <em>Mutually Exclusive, Collectively Exhaustive</em>. Inga överlappningar, täcker allt.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-green-600 dark:text-green-500 font-bold shrink-0">→</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Samma abstraktion:</strong> Håll samma detaljnivå inom samma lager (L1, L2).
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-green-600 dark:text-green-500 font-bold shrink-0">→</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Affärsspråk:</strong> Använd begrepp som verksamheten förstår, inte IT-termer.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-green-600 dark:text-green-500 font-bold shrink-0">→</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Tidsresistent:</strong> Förmågan ska vara giltig även om organisationen omstruktureras.
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-neutral-900 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800">
                    <h3 class="font-bold text-red-600 dark:text-red-500 mb-4 text-lg">❌ Undvik detta</h3>
                    <ul class="space-y-3 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex gap-3">
                            <span class="text-red-600 dark:text-red-500 font-bold shrink-0">×</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Systemnamn:</strong> "SAP" är ingen förmåga. "Ekonomistyrning" är.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-red-600 dark:text-red-500 font-bold shrink-0">×</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Organisationsenheter:</strong> Avdelningar byter namn, förmågor består.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-red-600 dark:text-red-500 font-bold shrink-0">×</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Processverb:</strong> Undvik att beskriva flöden (steg 1, steg 2).
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-red-600 dark:text-red-500 font-bold shrink-0">×</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Blandade nivåer:</strong> Blanda inte strategiska mål med operativa funktioner.
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-red-600 dark:text-red-500 font-bold shrink-0">×</span>
                            <div>
                                <strong class="text-gray-900 dark:text-neutral-100">Teknisk jargong:</strong> "Backend API-orkestrering" säger inget till verksamheten.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- MECE-fördjupning -->
        <section class="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-950/20 dark:to-blue-950/20 p-6 rounded-xl border border-purple-200 dark:border-purple-800">
            <h3 class="text-xl font-bold text-purple-900 dark:text-purple-300 mb-4">🎯 Fördjupning: MECE-principen</h3>
            <p class="text-gray-700 dark:text-neutral-300 mb-4">
                MECE (Mutually Exclusive, Collectively Exhaustive) innebär att förmågor på samma nivå:
            </p>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="font-bold text-purple-800 dark:text-purple-400 mb-2">Mutually Exclusive</h4>
                    <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2">Ingen överlappning - varje förmåga ska ha ett tydligt avgränsat ansvar.</p>
                    <div class="text-xs bg-red-50 dark:bg-red-950/30 p-2 rounded border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
                        ❌ Dåligt: "Kundkommunikation" + "E-post till kunder"<br>
                        <span class="text-gray-600 dark:text-neutral-400">(E-post är ju kundkommunikation!)</span>
                    </div>
                    <div class="text-xs bg-green-50 dark:bg-green-950/30 p-2 rounded border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 mt-2">
                        ✅ Bra: "Kundkommunikation" + "Produktinformation"
                    </div>
                </div>
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <h4 class="font-bold text-purple-800 dark:text-purple-400 mb-2">Collectively Exhaustive</h4>
                    <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2">Täcker allt - inga "vita fläckar" där ansvar saknas.</p>
                    <div class="text-xs bg-red-50 dark:bg-red-950/30 p-2 rounded border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300">
                        ❌ Dåligt: Bara "Löneutbetalning"<br>
                        <span class="text-gray-600 dark:text-neutral-400">(Vad händer med rekrytering, kompetensutveckling?)</span>
                    </div>
                    <div class="text-xs bg-green-50 dark:bg-green-950/30 p-2 rounded border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 mt-2">
                        ✅ Bra: "Rekrytering" + "Kompetensutveckling" + "Löneadministration" + "Arbetsmiljö"
                    </div>
                </div>
            </div>
        </section>

        <!-- Struktur & Visuell Modell -->
        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">2. Struktur & Visuell Modell</h2>
            <p class="mb-6 text-gray-700 dark:text-neutral-300">
                Enligt best practice (inklusive Ineras referensarkitektur) bör kartan delas in i tre distinkta skikt (Stratification). Detta hjälper intressenter att snabbt orientera sig.
            </p>

            <div class="border-2 border-dashed border-gray-300 dark:border-neutral-700 rounded-xl p-4 bg-gray-100 dark:bg-neutral-900 flex flex-col gap-4 text-center font-bold text-sm text-gray-500 dark:text-neutral-400">

                <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 p-4 rounded text-indigo-800 dark:text-indigo-300">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-indigo-400 dark:text-indigo-500">Strategiskt Skikt (Direction)</span>
                    STYRANDE VERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">Strategi, Arkitektur, Policy, Kvalitet, Säkerhet</p>
                    <p class="text-xs font-normal mt-2 text-indigo-600 dark:text-indigo-400 italic">~10-15% av förmågorna</p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 p-8 rounded text-blue-900 dark:text-blue-300 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-blue-400 dark:text-blue-500">Värdeskapande Skikt (Core)</span>
                    KÄRNVERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">Det som är organisationens "raison d'être" (ex. Vård, Skola, Tillverkning)</p>
                    <p class="text-xs font-normal mt-2 text-blue-600 dark:text-blue-400 italic">~50-60% av förmågorna - detta är hjärtat</p>
                </div>

                <div class="bg-gray-200 dark:bg-neutral-800 border border-gray-300 dark:border-neutral-700 p-4 rounded text-gray-700 dark:text-neutral-300">
                    <span class="block text-xs uppercase tracking-wider mb-2 text-gray-400 dark:text-neutral-500">Stödjande Skikt (Enabling)</span>
                    STÖDJANDE VERKSAMHET
                    <p class="text-xs font-normal mt-1 text-gray-500 dark:text-neutral-400">HR, IT, Ekonomi, Fastighet, Juridik</p>
                    <p class="text-xs font-normal mt-2 text-gray-600 dark:text-neutral-400 italic">~30-40% av förmågorna</p>
                </div>
            </div>

            <div class="warning-box p-5 rounded-lg mt-6">
                <h3 class="font-bold text-orange-900 dark:text-orange-300 mb-2">⚠️ Vanligt fel: Oskarp gräns mellan Core och Enabling</h3>
                <p class="text-gray-700 dark:text-neutral-300 text-sm mb-2">
                    Exempel: Är "Kompetensutveckling" Core eller Enabling för ett universitet?
                </p>
                <ul class="text-sm text-gray-700 dark:text-neutral-300 space-y-1 ml-4">
                    <li>• <strong>Core</strong> om det handlar om att utbilda studenter (kärnuppdraget)</li>
                    <li>• <strong>Enabling</strong> om det handlar om att utveckla personalens kompetens</li>
                </ul>
                <p class="text-xs text-gray-600 dark:text-neutral-400 mt-2 italic">
                    Lösning: Var explicit. "Utbilda studenter" (Core) vs "Utveckla medarbetare" (Enabling)
                </p>
            </div>
        </section>

        <!-- Praktiska Exempel -->
        <section id="exempel">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">3. Praktiska Exempel</h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Exempel: Kommun -->
                <div class="example-card bg-white dark:bg-neutral-900 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 text-white">
                        <h3 class="font-bold text-lg">🏛️ Kommun</h3>
                        <p class="text-xs text-blue-100 mt-1">Exempel från kommunal verksamhet</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Direction</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Samhällsplanering, Demokratisk styrning</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Core</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Utbildning, Äldreomsorg, Socialtjänst, Teknisk försörjning</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-600 dark:text-neutral-400 uppercase">Enabling</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">HR, Ekonomi, IT, Fastighetsförvaltning</p>
                        </div>
                    </div>
                </div>

                <!-- Exempel: Sjukhus -->
                <div class="example-card bg-white dark:bg-neutral-900 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-green-700 p-4 text-white">
                        <h3 class="font-bold text-lg">🏥 Sjukhus</h3>
                        <p class="text-xs text-green-100 mt-1">Exempel från vårdsektorn</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Direction</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Vårdkvalitet, Patientsäkerhet, Medicinsk utveckling</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Core</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Diagnostik, Behandling, Akutvård, Rehabilitering</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-600 dark:text-neutral-400 uppercase">Enabling</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Medicinsk dokumentation, Laboratoriestöd, HR, Ekonomi</p>
                        </div>
                    </div>
                </div>

                <!-- Exempel: E-handel -->
                <div class="example-card bg-white dark:bg-neutral-900 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-4 text-white">
                        <h3 class="font-bold text-lg">🛒 E-handel</h3>
                        <p class="text-xs text-purple-100 mt-1">Exempel från detaljhandel online</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Direction</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Varumärkesstrategi, Kategoriplanering, Konkurrensanalys</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Core</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Produktpresentation, Order & betalning, Kundservice, Leverans</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-600 dark:text-neutral-400 uppercase">Enabling</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Lagerhantering, IT-plattform, Ekonomi, Marknadsföring</p>
                        </div>
                    </div>
                </div>

                <!-- Exempel: Tillverkningsföretag -->
                <div class="example-card bg-white dark:bg-neutral-900 rounded-lg shadow-sm border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-orange-700 p-4 text-white">
                        <h3 class="font-bold text-lg">🏭 Tillverkning</h3>
                        <p class="text-xs text-orange-100 mt-1">Exempel från industri</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase">Direction</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Produktstrategi, Kvalitetsledning, Hållbarhetsstyrning</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase">Core</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Produktutveckling, Produktion, Kvalitetskontroll, Leverans</p>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-gray-600 dark:text-neutral-400 uppercase">Enabling</span>
                            <p class="text-sm text-gray-700 dark:text-neutral-300">Underhåll, Inköp, HR, Ekonomi, IT</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Attribut/Metadata -->
        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">4. Att beskriva en förmåga</h2>
            <p class="mb-4 text-gray-700 dark:text-neutral-300">
                Enbart en "box" räcker inte. För att förmågan ska vara användbar i din applikation och för arkitekturell analys bör följande metadata finnas definierad.
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
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hur väl fungerar förmågan idag? (1-5 skala enligt CMMI)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">3 (Definierad)</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Affärsvärde</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hur kritisk är denna förmåga för strategin?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Hög / Kritisk</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Målmognad</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Önskad mognadsnivå (driver investeringsbehov)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">4 (Managed)</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Personer (People)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka roller eller kompetenser krävs?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Läkare, Kurator, Handläggare</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Process</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka processer realiserar denna förmåga?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Remissflöde, Beslutsprocess</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Teknik (Technology)</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka applikationer stöttar förmågan?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Cambio Cosmic, Visma</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Information</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilka informationsobjekt används/produceras?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Patientjournal, Beslut</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-mono text-blue-600 dark:text-blue-400">Ansvarig</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Vilken funktion äger utvecklingen?</td>
                            <td class="p-3 text-gray-700 dark:text-neutral-300">Medicinsk chef</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="tip-box p-5 rounded-lg mt-6">
                <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2">💡 Tips: Heat Mapping</h3>
                <p class="text-gray-700 dark:text-neutral-300">
                    Kombinera attribut för att skapa "heat maps" som visar:
                </p>
                <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-neutral-300 ml-4">
                    <li>• Gap-analys: Förmågor med låg mognad men högt affärsvärde = prioriterade investeringar</li>
                    <li>• Teknisk skuld: Förmågor med många legacy-system</li>
                    <li>• Kompetensrisker: Kritiska förmågor med få nyckelpersoner</li>
                </ul>
            </div>
        </section>

        <!-- Vanliga Misstag -->
        <section>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-neutral-100 mb-6 border-b border-gray-200 dark:border-neutral-800 pb-2">5. Vanliga Misstag & Lösningar</h2>
            
            <div class="space-y-4">
                <details class="bg-white dark:bg-neutral-900 rounded-lg border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <summary class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 font-semibold text-gray-900 dark:text-neutral-100">
                        ❌ Misstag 1: "Vi skriver bara systemnamnPå boxarna"
                    </summary>
                    <div class="p-4 border-t border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-950">
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Problem:</strong> System kommer och går. "SAP" säger inget om förmågan.</p>
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Lösning:</strong> Börja med affärsförmågan ("Ekonomistyrning"), länka sedan system som stödjer den.</p>
                        <div class="text-xs bg-green-50 dark:bg-green-950/30 p-3 rounded border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 mt-2">
                            ✅ Rätt: Förmåga "Ekonomistyrning" → stöds av applikationer [SAP, Excel, Power BI]
                        </div>
                    </div>
                </details>

                <details class="bg-white dark:bg-neutral-900 rounded-lg border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <summary class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 font-semibold text-gray-900 dark:text-neutral-100">
                        ❌ Misstag 2: "Allt är core-verksamhet!"
                    </summary>
                    <div class="p-4 border-t border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-950">
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Problem:</strong> Om allt är core, så är inget core. Strategiskt fokus förloras.</p>
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Lösning:</strong> Fråga "Skulle vi upphöra att existera om vi inte gjorde detta?" Om ja → Core. Om nej → Enabling eller Direction.</p>
                        <div class="text-xs bg-blue-50 dark:bg-blue-950/30 p-3 rounded border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-300 mt-2">
                            Exempel: För ett sjukhus är "Behandla patienter" core, men "Driva IT-support" är enabling (kan köpas in).
                        </div>
                    </div>
                </details>

                <details class="bg-white dark:bg-neutral-900 rounded-lg border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <summary class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 font-semibold text-gray-900 dark:text-neutral-100">
                        ❌ Misstag 3: "För många nivåer (L1, L2, L3, L4...)"
                    </summary>
                    <div class="p-4 border-t border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-950">
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Problem:</strong> Kartan blir oöverskådlig, folk tröttnar.</p>
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Lösning:</strong> Håll dig till max 2-3 nivåer. L1 (strategisk), L2 (taktisk) räcker oftast. L3 kan vara detaljnivå för specialister.</p>
                        <div class="text-xs bg-purple-50 dark:bg-purple-950/30 p-3 rounded border border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-300 mt-2">
                            Tumregel: 7-12 förmågor på L1, varje L1 har 3-8 barn på L2. Totalt ~50-100 förmågor på L2 för en stor organisation.
                        </div>
                    </div>
                </details>

                <details class="bg-white dark:bg-neutral-900 rounded-lg border border-gray-200 dark:border-neutral-800 overflow-hidden">
                    <summary class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800 font-semibold text-gray-900 dark:text-neutral-100">
                        ❌ Misstag 4: "Vi gör kartan i PowerPoint och sen glömmer vi den"
                    </summary>
                    <div class="p-4 border-t border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-950">
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Problem:</strong> Statiska bilder blir snabbt inaktuella och är svåra att länka till resten av arkitekturen.</p>
                        <p class="text-sm text-gray-700 dark:text-neutral-300 mb-2"><strong>Lösning:</strong> Använd strukturerad data (Markdown, ArchiMate, databas) + dynamisk visualisering. Då kan du koppla till applikationer, processer, strategiska mål osv.</p>
                    </div>
                </details>
            </div>
        </section>

        <!-- Checklista -->
        <section class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 p-6 rounded-xl border border-green-200 dark:border-green-800">
            <h2 class="text-2xl font-bold text-green-900 dark:text-green-300 mb-4">✅ Kvalitetschecklista</h2>
            <p class="text-gray-700 dark:text-neutral-300 mb-4">Använd denna checklista innan du publicerar din karta:</p>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-400 mb-3">Innehåll</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Alla förmågor är substantiv (inte verb)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Inga systemnamn eller organisationsenheter</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>MECE på varje nivå (ingen överlappning, täcker allt)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Affärsspråk som verksamheten förstår</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-400 mb-3">Struktur</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Tydlig stratifiering (Direction/Core/Enabling)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Konsekvent abstraktion inom varje nivå</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Max 2-3 hierarkiska nivåer</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>7±2 förmågor per överordnad förmåga</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-400 mb-3">Metadata</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Mognadsnivå definierad för kritiska förmågor</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Affärsvärde/strategisk vikt angiven</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Länkar till stödjande system där relevant</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Ansvarig funktion/roll identifierad</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white dark:bg-neutral-900 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <h3 class="font-semibold text-green-800 dark:text-green-400 mb-3">Validering</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-neutral-300">
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Verksamma har granskat och förstår kartan</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Kartan är stabil även vid omorganisation</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Kan användas för gap-analys och planering</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <input type="checkbox" class="mt-1">
                            <span>Dokumentation/definitioner finns tillgängliga</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Referenser -->
        <section class="bg-slate-50 dark:bg-neutral-900/50 p-6 rounded-xl border border-slate-200 dark:border-neutral-800">
            <h2 class="text-xl font-bold text-slate-900 dark:text-neutral-100 mb-4">📚 Referenser & Läsning</h2>
            <div class="grid md:grid-cols-2 gap-4">

                <a href="https://www.inera.se/arkitektur/" target="_blank" rel="noopener" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-blue-500 dark:hover:border-blue-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 text-xs font-bold px-2 py-1 rounded">Sverige</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-blue-700 dark:group-hover:text-blue-400">Inera Arkitektur</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Nationell referensarkitektur för vård och omsorg. Innehåller bra exempel på förmågekartor för offentlig sektor.</p>
                </a>

                <a href="https://pubs.opengroup.org/togaf-standard/business-architecture/business-capabilities.html" target="_blank" rel="noopener" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-purple-500 dark:hover:border-purple-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-purple-100 dark:bg-purple-950 text-purple-800 dark:text-purple-300 text-xs font-bold px-2 py-1 rounded">Global Standard</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-purple-700 dark:group-hover:text-purple-400">TOGAF Series Guide</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">The Open Groups officiella guide för Business Capabilities. Den "bibel" som de flesta enterprise arkitekter följer.</p>
                </a>

                <a href="https://www.businessarchitectureguild.org/" target="_blank" rel="noopener" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-green-500 dark:hover:border-green-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-green-100 dark:bg-green-950 text-green-800 dark:text-green-300 text-xs font-bold px-2 py-1 rounded">Deep Dive</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-green-700 dark:group-hover:text-green-400">BIZBOK® Guide</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Business Architecture Body of Knowledge. Går djupt in på hur man mappar capabilities mot värdeströmmar.</p>
                </a>

                 <a href="https://www.apqc.org/" target="_blank" rel="noopener" class="resource-link block bg-white dark:bg-neutral-900 p-4 rounded border border-gray-200 dark:border-neutral-800 hover:border-orange-500 dark:hover:border-orange-500 group">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-orange-100 dark:bg-orange-950 text-orange-800 dark:text-orange-300 text-xs font-bold px-2 py-1 rounded">Ramverk</span>
                        <h3 class="font-bold text-gray-900 dark:text-neutral-100 group-hover:text-orange-700 dark:group-hover:text-orange-400">APQC PCF</h3>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">Process Classification Framework. Bra inspiration om man har svårt att hitta namn på förmågor (även om det är processfokus).</p>
                </a>

            </div>
        </section>

        <!-- CTA till Wizard -->
        <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-8 rounded-xl text-center">
            <h2 class="text-3xl font-bold mb-4">Redo att skapa din första förmåga?</h2>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto">
                Använd vår steg-för-steg guide som hjälper dig genom hela processen – från namngivning till metadata.
            </p>
            <a href="<?= h(base_path('view/capability-map-wizard.php')) ?>" 
               class="inline-flex items-center gap-2 px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-blue-50 transition shadow-xl hover:shadow-2xl text-lg">
                🧭 Starta Wizard
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </section>

    </main>

    <footer class="bg-slate-900 dark:bg-neutral-950 text-slate-400 dark:text-neutral-500 py-8 text-center text-sm border-t border-slate-800 dark:border-neutral-900">
        <p>&copy; 2025 Capability Map Guide. Baserat på TOGAF och Inera-ramverket.</p>
    </footer>

</body>
</html>
