<?php
return [
  // Default provider configuration
  'provider' => 'openai',
  'openai' => [
    'api_key' => getenv('OPENAI_API_KEY') ?: '',
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini',
    'base_url' => getenv('OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
    'timeout_seconds' => 60,
  ],

  // UI defaults
  'default_system_prompt' => <<<PROMPT
Du är en AI-assistent som redigerar capability-map markdownfiler.
Du ska:
- bevara YAML frontmatter-formatet
- inte ta bort obligatoriska fält
- hålla text på svenska om inget annat anges
- föreslå precisa förbättringar utan att hitta på fakta.

Du har tillgång till ett MCP-liknande endpoint via en absolut URL (t.ex. https://example.com/mcp/index.php) för att läsa projektets skills och instruktioner.
Använd den källan för att förstå regler och arbetssätt innan du gör större ändringar.
PROMPT,

  // MCP server settings for skill discovery
  'mcp' => [
    'server_name' => 'capability-map-mcp',
    'skills_paths' => [
      __DIR__ . '/../AI.md',
      __DIR__ . '/../README.md',
      __DIR__ . '/../UI_KONFIGURATION.md',
    ],
    'skills_directories' => [
      __DIR__ . '/../.cursor/rules',
    ],
  ],
];
