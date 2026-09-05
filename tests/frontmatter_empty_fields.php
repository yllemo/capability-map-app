<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/Frontmatter.php';

use App\Frontmatter;

$parsed = Frontmatter::parse("---\nid: cap-test-001\nname: Test\nowner: \nstatus: \ndescription: \ntags:\n  - test\n  - exempel\nmaturity: 3\nempty_list: []\nlast_empty:\n---\n\nBeskrivning\n");
foreach (['owner', 'status', 'description', 'last_empty'] as $field) {
  if ($parsed['meta'][$field] !== '') throw new RuntimeException("$field ska vara tom text.");
  // Same strict string argument used when rendering editor input values.
  htmlspecialchars($parsed['meta'][$field], ENT_QUOTES, 'UTF-8');
}
if ($parsed['meta']['tags'] !== ['test', 'exempel']) throw new RuntimeException('Listor ska bevaras.');
if ($parsed['meta']['empty_list'] !== []) throw new RuntimeException('Explicita tomma listor ska bevaras.');
if ($parsed['meta']['maturity'] !== 3) throw new RuntimeException('Efterföljande metadata ska bevaras.');
if (trim($parsed['body']) !== 'Beskrivning') throw new RuntimeException('Brödtext ska bevaras.');
echo "Empty frontmatter field tests passed\n";
