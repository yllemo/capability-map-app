<?php require __DIR__ . '/../app/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="sv" class="antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capability Map Wizard - Skapa din förmåga steg-för-steg</title>
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
        .step { display: none; }
        .step.active { display: block; }
        .progress-step { transition: all 0.3s ease; }
        .progress-step.completed { background: #10b981; color: white; }
        .progress-step.active { background: #3b82f6; color: white; }
        .validation-error { display: none; }
        .validation-error.show { display: block; }
        .example-box { cursor: pointer; transition: all 0.2s; }
        .example-box:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .example-box.selected { border-color: #3b82f6; background: #eff6ff; }
        .dark .example-box.selected { border-color: #60a5fa; background: rgba(59, 130, 246, 0.1); }
    </style>
    <link rel="stylesheet" href="<?= h(base_path('assets/view.css')) ?>">
    <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body class="bg-slate-50 dark:bg-neutral-950 text-gray-800 dark:text-neutral-100">

    <header class="bg-slate-900 dark:bg-neutral-900 text-white py-8 border-b border-slate-800 dark:border-neutral-800">
        <div class="max-w-4xl mx-auto px-6">
            <div class="flex items-center justify-between mb-4">
              <a href="<?= h(base_path('view/capability-map-best-practices.php')) ?>" class="inline-flex items-center gap-2 text-sm text-slate-300 dark:text-neutral-400 hover:text-white dark:hover:text-neutral-200">
                ← Tillbaka till Best Practices
              </a>
              <button class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-slate-700 dark:border-neutral-700 bg-slate-800 dark:bg-neutral-800 hover:bg-slate-700 dark:hover:bg-neutral-700 transition"
                      type="button" data-theme-toggle aria-label="Växla tema">🌓</button>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">🧭 Capability Map Wizard</h1>
            <p class="text-slate-300 dark:text-neutral-300">Skapa din förmåga steg-för-steg med validering och hjälp</p>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-12">

        <!-- Progress Indicator -->
        <div class="mb-12">
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1 flex items-center">
                    <div class="progress-step active flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-neutral-700 text-sm font-bold" data-step="1">1</div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-neutral-700"></div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="progress-step flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-neutral-700 text-sm font-bold" data-step="2">2</div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-neutral-700"></div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="progress-step flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-neutral-700 text-sm font-bold" data-step="3">3</div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-neutral-700"></div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="progress-step flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-neutral-700 text-sm font-bold" data-step="4">4</div>
                    <div class="flex-1 h-1 bg-gray-300 dark:bg-neutral-700"></div>
                </div>
                <div class="progress-step flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 dark:bg-neutral-700 text-sm font-bold" data-step="5">5</div>
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-neutral-400">
                <span>Namn</span>
                <span>Nivå</span>
                <span>Metadata</span>
                <span>Validering</span>
                <span>Klar</span>
            </div>
        </div>

        <form id="capabilityForm">

            <!-- Step 1: Namngivning -->
            <div class="step active" data-step="1">
                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-neutral-100 mb-6">Steg 1: Namnge din förmåga</h2>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                            Förmågans namn <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="capabilityName" 
                               class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100"
                               placeholder="ex. Rekrytering, Ekonomistyrning, Patientvård">
                        <div class="validation-error text-red-600 dark:text-red-400 text-sm mt-2" id="nameError"></div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">💡 Tips för bra namngivning:</h3>
                        <ul class="text-sm text-gray-700 dark:text-neutral-300 space-y-1">
                            <li>✅ Använd <strong>substantiv</strong>: "Rekrytering" inte "Rekrytera"</li>
                            <li>✅ Var <strong>specifik men inte för teknisk</strong>: "Ekonomistyrning" inte "SAP"</li>
                            <li>✅ Använd <strong>affärsspråk</strong> som verksamheten förstår</li>
                            <li>❌ Undvik processverb, systemnamn och organisationsenheter</li>
                        </ul>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-3">
                            Eller välj från vanliga exempel:
                        </label>
                        <div class="grid md:grid-cols-2 gap-3">
                            <div class="example-box border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-3 hover:border-blue-500 dark:hover:border-blue-500" onclick="selectExample(this, 'Rekrytering')">
                                <div class="font-semibold text-gray-900 dark:text-neutral-100">Rekrytering</div>
                                <div class="text-xs text-gray-500 dark:text-neutral-400">HR / Enabling</div>
                            </div>
                            <div class="example-box border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-3 hover:border-blue-500 dark:hover:border-blue-500" onclick="selectExample(this, 'Ekonomistyrning')">
                                <div class="font-semibold text-gray-900 dark:text-neutral-100">Ekonomistyrning</div>
                                <div class="text-xs text-gray-500 dark:text-neutral-400">Ekonomi / Enabling</div>
                            </div>
                            <div class="example-box border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-3 hover:border-blue-500 dark:hover:border-blue-500" onclick="selectExample(this, 'Patientvård')">
                                <div class="font-semibold text-gray-900 dark:text-neutral-100">Patientvård</div>
                                <div class="text-xs text-gray-500 dark:text-neutral-400">Sjukvård / Core</div>
                            </div>
                            <div class="example-box border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-3 hover:border-blue-500 dark:hover:border-blue-500" onclick="selectExample(this, 'Strategi & styrning')">
                                <div class="font-semibold text-gray-900 dark:text-neutral-100">Strategi & styrning</div>
                                <div class="text-xs text-gray-500 dark:text-neutral-400">Ledning / Direction</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                            Kort beskrivning
                        </label>
                        <textarea id="capabilityDescription" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100"
                                  placeholder="Beskriv vad denna förmåga handlar om..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 2: Stratification (Layer) -->
            <div class="step" data-step="2">
                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-neutral-100 mb-6">Steg 2: Vilket lager tillhör förmågan?</h2>
                    
                    <p class="text-gray-600 dark:text-neutral-400 mb-6">
                        Förmågekartor delas in i tre skikt. Välj det som passar bäst:
                    </p>

                    <div class="space-y-4">
                        <label class="layer-option block border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-5 cursor-pointer hover:border-indigo-500 dark:hover:border-indigo-500 transition">
                            <input type="radio" name="layer" value="direction" class="mr-3">
                            <div class="inline-block">
                                <div class="font-bold text-indigo-800 dark:text-indigo-400 text-lg">Strategiskt Skikt (Direction)</div>
                                <div class="text-sm text-gray-600 dark:text-neutral-400 mt-1">
                                    Styrande verksamhet: Strategi, arkitektur, policy, kvalitetsledning, säkerhet
                                </div>
                                <div class="text-xs text-indigo-600 dark:text-indigo-400 mt-2 italic">
                                    Exempel: "Verksamhetsstyrning", "Arkitekturförvaltning", "Säkerhetsstyrning"
                                </div>
                            </div>
                        </label>

                        <label class="layer-option block border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-5 cursor-pointer hover:border-blue-500 dark:hover:border-blue-500 transition">
                            <input type="radio" name="layer" value="core" class="mr-3">
                            <div class="inline-block">
                                <div class="font-bold text-blue-800 dark:text-blue-400 text-lg">Värdeskapande Skikt (Core)</div>
                                <div class="text-sm text-gray-600 dark:text-neutral-400 mt-1">
                                    Kärnverksamhet: Det som är organisationens primära uppdrag och "raison d'être"
                                </div>
                                <div class="text-xs text-blue-600 dark:text-blue-400 mt-2 italic">
                                    Exempel: "Patientvård" (sjukhus), "Utbildning" (skola), "Produktutveckling" (industri)
                                </div>
                            </div>
                        </label>

                        <label class="layer-option block border-2 border-gray-200 dark:border-neutral-700 rounded-lg p-5 cursor-pointer hover:border-gray-500 dark:hover:border-gray-500 transition">
                            <input type="radio" name="layer" value="enabling" class="mr-3">
                            <div class="inline-block">
                                <div class="font-bold text-gray-800 dark:text-gray-400 text-lg">Stödjande Skikt (Enabling)</div>
                                <div class="text-sm text-gray-600 dark:text-neutral-400 mt-1">
                                    Stödjande verksamhet: HR, IT, ekonomi, fastighet, juridik – möjliggör core
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 italic">
                                    Exempel: "Rekrytering", "Ekonomistyrning", "IT-förvaltning", "Lokalvård"
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="validation-error text-red-600 dark:text-red-400 text-sm mt-4" id="layerError"></div>

                    <div class="bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mt-6">
                        <h3 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">🤔 Osäker? Fråga dig själv:</h3>
                        <p class="text-sm text-gray-700 dark:text-neutral-300">
                            "Om vi slutade göra detta, skulle organisationen upphöra att existera?"<br>
                            <strong>Ja</strong> → Troligen <strong>Core</strong><br>
                            <strong>Nej</strong> → Troligen <strong>Enabling</strong> (eller kan köpas in)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 3: Metadata -->
            <div class="step" data-step="3">
                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-neutral-100 mb-6">Steg 3: Metadata & Egenskaper</h2>
                    
                    <div class="space-y-6">
                        <!-- Maturity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                                Nuvarande mognadsnivå
                            </label>
                            <select id="maturity" class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100">
                                <option value="">Välj mognadsnivå...</option>
                                <option value="1">1 - Initial (Ad-hoc, ostrukturerat)</option>
                                <option value="2">2 - Repeatable (Vissa rutiner finns)</option>
                                <option value="3">3 - Defined (Dokumenterade processer)</option>
                                <option value="4">4 - Managed (Mäts och följs upp)</option>
                                <option value="5">5 - Optimizing (Kontinuerlig förbättring)</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1">
                                Baserat på CMMI (Capability Maturity Model Integration)
                            </p>
                        </div>

                        <!-- Business Value -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                                Affärsvärde / Strategisk vikt
                            </label>
                            <select id="businessValue" class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100">
                                <option value="">Välj affärsvärde...</option>
                                <option value="critical">Kritisk - Nödvändig för överlevnad</option>
                                <option value="high">Hög - Mycket viktig för strategin</option>
                                <option value="medium">Medel - Viktigt men inte avgörande</option>
                                <option value="low">Låg - Stödfunktion</option>
                            </select>
                        </div>

                        <!-- Target Maturity -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                                Målmognad (önskad nivå)
                            </label>
                            <select id="targetMaturity" class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100">
                                <option value="">Välj målmognad...</option>
                                <option value="1">1 - Initial</option>
                                <option value="2">2 - Repeatable</option>
                                <option value="3">3 - Defined</option>
                                <option value="4">4 - Managed</option>
                                <option value="5">5 - Optimizing</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1">
                                Skillnaden mellan nuläge och målmognad visar investeringsbehov
                            </p>
                        </div>

                        <!-- Technologies -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                                Stödjande system/applikationer
                            </label>
                            <input type="text" 
                                   id="technologies" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100"
                                   placeholder="ex. Cambio Cosmic, Visma, SharePoint (kommaseparerat)">
                            <p class="text-xs text-gray-500 dark:text-neutral-400 mt-1">
                                Vilka IT-system stödjer denna förmåga?
                            </p>
                        </div>

                        <!-- Owner -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-neutral-300 mb-2">
                                Ansvarig funktion/roll
                            </label>
                            <input type="text" 
                                   id="owner" 
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg bg-white dark:bg-neutral-950 text-gray-900 dark:text-neutral-100"
                                   placeholder="ex. HR-chef, IT-chef, Medicinskt ansvarig sjuksköterska">
                        </div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-6">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">💡 Varför metadata?</h3>
                        <p class="text-sm text-gray-700 dark:text-neutral-300">
                            Metadata gör att du kan skapa "heat maps" och prioriteringsanalyser. Exempel:
                        </p>
                        <ul class="text-sm text-gray-700 dark:text-neutral-300 mt-2 space-y-1 ml-4">
                            <li>• <strong>Gap-analys:</strong> Låg mognad + högt värde = prioriterad investering</li>
                            <li>• <strong>Teknisk skuld:</strong> Kritiska förmågor med legacy-system</li>
                            <li>• <strong>Kompetensrisk:</strong> Viktiga förmågor med få nyckelkompetenser</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Step 4: Validering -->
            <div class="step" data-step="4">
                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-neutral-100 mb-6">Steg 4: Validering</h2>
                    
                    <p class="text-gray-600 dark:text-neutral-400 mb-6">
                        Kontrollera att din förmåga följer best practices:
                    </p>

                    <div class="space-y-4" id="validationChecks">
                        <!-- Validation checks injiceras med JavaScript -->
                    </div>

                    <div id="validationSummary" class="mt-6"></div>
                </div>
            </div>

            <!-- Step 5: Sammanfattning & Export -->
            <div class="step" data-step="5">
                <div class="bg-white dark:bg-neutral-900 rounded-xl shadow-sm border border-gray-200 dark:border-neutral-800 p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 text-4xl mb-4">
                            ✓
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-neutral-100 mb-2">Grattis!</h2>
                        <p class="text-gray-600 dark:text-neutral-400">Din förmåga är klar att användas</p>
                    </div>

                    <div class="bg-slate-50 dark:bg-neutral-950 rounded-lg p-6 mb-6" id="summary">
                        <!-- Sammanfattning injiceras med JavaScript -->
                    </div>

                    <div class="border-t border-gray-200 dark:border-neutral-800 pt-6">
                        <h3 class="font-semibold text-gray-900 dark:text-neutral-100 mb-4">Exportera till:</h3>
                        <div class="grid md:grid-cols-3 gap-4">
                            <button type="button" onclick="exportMarkdown()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition">
                                📄 Markdown
                            </button>
                            <button type="button" onclick="exportJSON()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                                📦 JSON
                            </button>
                            <button type="button" onclick="exportArchiMate()" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold transition">
                                🏛️ ArchiMate CSV
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="button" onclick="resetWizard()" class="text-blue-600 dark:text-blue-400 hover:underline">
                            Skapa en till förmåga
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <button type="button" id="prevBtn" onclick="changeStep(-1)" class="px-6 py-3 border border-gray-300 dark:border-neutral-700 rounded-lg font-semibold hover:bg-gray-50 dark:hover:bg-neutral-800 transition" style="display: none;">
                    ← Föregående
                </button>
                <div class="flex-1"></div>
                <button type="button" id="nextBtn" onclick="changeStep(1)" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                    Nästa →
                </button>
            </div>

        </form>

    </main>

    <script>
        let currentStep = 1;
        const totalSteps = 5;
        const formData = {};

        function changeStep(direction) {
            // Validate current step before proceeding
            if (direction > 0 && !validateStep(currentStep)) {
                return;
            }

            // Save current step data
            saveStepData(currentStep);

            // Hide current step
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('completed');

            // Calculate new step
            currentStep += direction;
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps) currentStep = totalSteps;

            // Show new step
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
            document.querySelector(`.progress-step[data-step="${currentStep}"]`).classList.add('active');

            // Update buttons
            document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').textContent = currentStep === totalSteps ? 'Klar' : 'Nästa →';
            document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'block';

            // Special handling for validation step
            if (currentStep === 4) {
                runValidation();
            }

            // Special handling for summary step
            if (currentStep === 5) {
                showSummary();
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            let isValid = true;

            if (step === 1) {
                const name = document.getElementById('capabilityName').value.trim();
                const errorEl = document.getElementById('nameError');
                
                if (!name) {
                    errorEl.textContent = 'Vänligen ange ett namn för förmågan';
                    errorEl.classList.add('show');
                    isValid = false;
                } else if (name.match(/\b(rekrytera|hantera|skapa|bygga|utveckla)\b/i)) {
                    errorEl.textContent = 'Undvik verb! Använd substantiv istället. Ex: "Rekrytering" istället för "Rekrytera"';
                    errorEl.classList.add('show');
                    isValid = false;
                } else {
                    errorEl.classList.remove('show');
                }
            }

            if (step === 2) {
                const layer = document.querySelector('input[name="layer"]:checked');
                const errorEl = document.getElementById('layerError');
                
                if (!layer) {
                    errorEl.textContent = 'Vänligen välj vilket lager förmågan tillhör';
                    errorEl.classList.add('show');
                    isValid = false;
                } else {
                    errorEl.classList.remove('show');
                }
            }

            return isValid;
        }

        function saveStepData(step) {
            if (step === 1) {
                formData.name = document.getElementById('capabilityName').value.trim();
                formData.description = document.getElementById('capabilityDescription').value.trim();
            }
            if (step === 2) {
                const layer = document.querySelector('input[name="layer"]:checked');
                formData.layer = layer ? layer.value : '';
            }
            if (step === 3) {
                formData.maturity = document.getElementById('maturity').value;
                formData.businessValue = document.getElementById('businessValue').value;
                formData.targetMaturity = document.getElementById('targetMaturity').value;
                formData.technologies = document.getElementById('technologies').value;
                formData.owner = document.getElementById('owner').value;
            }
        }

        function runValidation() {
            const checks = [
                {
                    text: 'Namnet är ett substantiv (inte verb)',
                    valid: !formData.name.match(/\b(rekrytera|hantera|skapa|bygga|utveckla|utföra)\b/i),
                    tip: 'Bra! Substantivbaserade namn är mer stabila över tid.'
                },
                {
                    text: 'Namnet innehåller inte systemnamn',
                    valid: !formData.name.match(/\b(SAP|Visma|SharePoint|Oracle|Excel)\b/i),
                    tip: 'Perfekt! System kommer och går, förmågor består.'
                },
                {
                    text: 'Namnet innehåller inte organisationsenheter',
                    valid: !formData.name.match(/\b(avdelning|enhet|kontor|sektion)\b/i),
                    tip: 'Utmärkt! Förmågor ska vara oberoende av organisation.'
                },
                {
                    text: 'Lager (Direction/Core/Enabling) är valt',
                    valid: !!formData.layer,
                    tip: 'Bra! Tydlig stratifiering hjälper alla att förstå kartan.'
                }
            ];

            const checksContainer = document.getElementById('validationChecks');
            checksContainer.innerHTML = '';

            let allValid = true;

            checks.forEach(check => {
                const div = document.createElement('div');
                div.className = `border-2 rounded-lg p-4 ${check.valid ? 'border-green-500 bg-green-50 dark:bg-green-950/30' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-950/30'}`;
                div.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="text-2xl">${check.valid ? '✅' : '⚠️'}</div>
                        <div class="flex-1">
                            <div class="font-semibold ${check.valid ? 'text-green-900 dark:text-green-300' : 'text-yellow-900 dark:text-yellow-300'}">${check.text}</div>
                            ${check.valid ? `<div class="text-sm text-green-700 dark:text-green-400 mt-1">${check.tip}</div>` : ''}
                        </div>
                    </div>
                `;
                checksContainer.appendChild(div);

                if (!check.valid) allValid = false;
            });

            const summary = document.getElementById('validationSummary');
            if (allValid) {
                summary.innerHTML = `
                    <div class="bg-green-50 dark:bg-green-950/30 border border-green-500 rounded-lg p-4 text-center">
                        <div class="text-3xl mb-2">🎉</div>
                        <div class="font-bold text-green-900 dark:text-green-300">Perfekt! Din förmåga följer alla best practices.</div>
                    </div>
                `;
            } else {
                summary.innerHTML = `
                    <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-500 rounded-lg p-4">
                        <div class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Några förbättringsförslag:</div>
                        <p class="text-sm text-blue-700 dark:text-blue-400">Du kan fortsätta ändå, men överväg att justera de markerade punkterna för bästa resultat.</p>
                    </div>
                `;
            }
        }

        function showSummary() {
            const layerNames = {
                direction: 'Strategiskt Skikt (Direction)',
                core: 'Värdeskapande Skikt (Core)',
                enabling: 'Stödjande Skikt (Enabling)'
            };

            const maturityNames = {
                '1': '1 - Initial',
                '2': '2 - Repeatable',
                '3': '3 - Defined',
                '4': '4 - Managed',
                '5': '5 - Optimizing'
            };

            const valueNames = {
                critical: 'Kritisk',
                high: 'Hög',
                medium: 'Medel',
                low: 'Låg'
            };

            let html = `
                <h3 class="font-bold text-xl text-gray-900 dark:text-neutral-100 mb-4">${formData.name}</h3>
                ${formData.description ? `<p class="text-gray-600 dark:text-neutral-400 mb-4">${formData.description}</p>` : ''}
                <dl class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Lager:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${layerNames[formData.layer] || 'Ej angett'}</dd>
                    </div>
                    ${formData.maturity ? `
                    <div>
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Mognad:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${maturityNames[formData.maturity]}</dd>
                    </div>
                    ` : ''}
                    ${formData.targetMaturity ? `
                    <div>
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Målmognad:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${maturityNames[formData.targetMaturity]}</dd>
                    </div>
                    ` : ''}
                    ${formData.businessValue ? `
                    <div>
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Affärsvärde:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${valueNames[formData.businessValue]}</dd>
                    </div>
                    ` : ''}
                    ${formData.technologies ? `
                    <div class="md:col-span-2">
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Stödjande system:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${formData.technologies}</dd>
                    </div>
                    ` : ''}
                    ${formData.owner ? `
                    <div class="md:col-span-2">
                        <dt class="font-semibold text-gray-700 dark:text-neutral-300">Ansvarig:</dt>
                        <dd class="text-gray-900 dark:text-neutral-100">${formData.owner}</dd>
                    </div>
                    ` : ''}
                </dl>
            `;

            document.getElementById('summary').innerHTML = html;
        }

        function selectExample(element, name) {
            // Remove selection from all examples
            document.querySelectorAll('.example-box').forEach(box => box.classList.remove('selected'));
            // Select clicked example
            element.classList.add('selected');
            // Set the name
            document.getElementById('capabilityName').value = name;
        }

        function exportMarkdown() {
            const md = `---
title: ${formData.name}
layer: ${formData.layer}
${formData.maturity ? `maturity: ${formData.maturity}` : ''}
${formData.targetMaturity ? `targetMaturity: ${formData.targetMaturity}` : ''}
${formData.businessValue ? `businessValue: ${formData.businessValue}` : ''}
${formData.owner ? `owner: ${formData.owner}` : ''}
${formData.technologies ? `technologies: [${formData.technologies}]` : ''}
---

# ${formData.name}

${formData.description || ''}
`;
            downloadFile('capability-' + formData.name.toLowerCase().replace(/\s+/g, '-') + '.md', md);
        }

        function exportJSON() {
            const json = JSON.stringify(formData, null, 2);
            downloadFile('capability-' + formData.name.toLowerCase().replace(/\s+/g, '-') + '.json', json);
        }

        function exportArchiMate() {
            const csv = `Type,Name,Documentation,Layer\nBusiness Capability,${formData.name},"${formData.description || ''}",${formData.layer}`;
            downloadFile('capability-' + formData.name.toLowerCase().replace(/\s+/g, '-') + '.csv', csv);
        }

        function downloadFile(filename, content) {
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        function resetWizard() {
            currentStep = 1;
            document.querySelectorAll('.step').forEach(step => step.classList.remove('active'));
            document.querySelector('.step[data-step="1"]').classList.add('active');
            document.querySelectorAll('.progress-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            document.querySelector('.progress-step[data-step="1"]').classList.add('active');
            document.getElementById('capabilityForm').reset();
            for (let key in formData) {
                delete formData[key];
            }
            document.getElementById('prevBtn').style.display = 'none';
            document.getElementById('nextBtn').textContent = 'Nästa →';
            document.getElementById('nextBtn').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Handle radio button selection styling
        document.querySelectorAll('input[name="layer"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.layer-option').forEach(option => {
                    option.classList.remove('border-blue-500', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-950/30');
                });
                this.closest('.layer-option').classList.add('border-blue-500', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-950/30');
            });
        });
    </script>

</body>
</html>
