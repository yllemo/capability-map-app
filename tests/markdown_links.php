<?php
declare(strict_types=1);
require __DIR__ . '/../app/lib/Markdown.php';
function base_path(string $path): string { return '/cap/' . $path; }
function get_selected_content_key(): string { return 'current'; }
function linkCheck(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
use App\Markdown;
$html = Markdown::toHtml('[Informationshantering](cap://other_map/cap-info-001)');
linkCheck(str_contains($html, '/cap/view/capability.php?id=cap-info-001&amp;map=other_map'), 'Länken måste ange vald målkarta och installationsprefix.');
linkCheck(!str_contains($html, '<em>'), 'Understreck i kartnyckeln får inte bli formatering.');
$html = Markdown::toHtml('[cap-info-001](cap://other/cap-info-001)');
linkCheck(substr_count($html, '<a ') === 1, 'Länktext med ID får inte skapa nästlade länkar.');
$html = Markdown::toHtml('`cap-info-001` och cap-info-002');
linkCheck(substr_count($html, '<a ') === 1 && str_contains($html, '<code>cap-info-001</code>'), 'Kod ska inte automatiskt länkas.');
$html = Markdown::toHtml('[Äldre länk](cap-info-001.md)');
linkCheck(str_contains($html, 'id=cap-info-001&amp;map=current'), 'Äldre länkar ska fortsätta fungera.');
$html = Markdown::toHtml('[Namn \[test\]](cap://other/cap-info-001)');
linkCheck(str_contains($html, '>Namn [test]</a>'), 'Hakparenteser i länktext ska bevaras.');
$html = Markdown::toHtml('[Extern](https://example.org/a_b?q=1&n=2)');
linkCheck(str_contains($html, 'href="https://example.org/a_b?q=1&amp;n=2"'), 'Externa URL:er ska bevaras.');
$html = Markdown::toHtml('[<script>](cap://other/cap-info-001)');
linkCheck(!str_contains($html, '<script>'), 'Länktext måste HTML-escapas.');
echo "Markdown link tests passed\n";
