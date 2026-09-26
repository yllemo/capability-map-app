<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/Frontmatter.php';
$template = App\Frontmatter::parse(file_get_contents(__DIR__ . '/../app/templates/capabilities/business-capability.md'));
foreach (['id', 'namn', 'typ', 'skikt', 'nivå', 'överordnad_förmåga', 'status', 'förmågeägare', 'version', 'senast_granskad', 'granskad_av', 'notation'] as $key) {
  if (!array_key_exists($key, $template['meta'])) throw new RuntimeException('Missing template key: ' . $key);
}
if (!str_contains($template['body'], '```mermaid') || !str_contains($template['body'], '## Kontrollfrågor före granskning')) throw new RuntimeException('Incomplete template body.');
$roundtrip = App\Frontmatter::parse("---\ntags:\n  - test\nförmågeägare: \"Ägare\"\nnivå: \"L2\"\n---\nText");
if ($roundtrip['meta']['förmågeägare'] !== 'Ägare' || $roundtrip['meta']['nivå'] !== 'L2' || $roundtrip['meta']['tags'] !== ['test']) throw new RuntimeException('Unicode keys after list failed.');
echo "Capability template tests passed\n";
