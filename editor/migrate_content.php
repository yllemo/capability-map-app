<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');
$error = '';
$warnings = [];
$plan = [];
$done = get_content_root() !== null;
$migration = new App\ContentMigration(__DIR__ . '/..');
try {
  if (!$done) $plan = $migration->plan(get_content_dirs());
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify((string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('Ogiltig session. Ladda om sidan.');
    if (!$done) {
      if (($_POST['backup'] ?? '') !== 'yes') throw new RuntimeException('Bekräfta att säkerhetskopian är klar.');
      $warnings = $migration->run(get_content_dirs());
      $done = true;
    }
  }
} catch (Throwable $e) {
  $error = $e->getMessage();
  $done = is_file(App\ContentMigration::configPath(dirname(__DIR__)));
}
$title = 'Flytta innehåll';
$activeNav = 'editor';
$content = '<div class="card"><div class="card__hd"><strong>Samla kartorna under /content</strong><a class="btn btn--ghost" href="index.php">Till editor</a></div><div class="card__bd">';
if ($error) $content .= '<p role="alert">'.h($error).'</p>';
if ($warnings) {
  $content .= '<p role="alert">Kartorna är migrerade, men följande original kunde inte städas bort:</p><ul>';
  foreach ($warnings as $warning) $content .= '<li>'.h($warning).'</li>';
  $content .= '</ul>';
}
if ($done) {
  $content .= '<p role="status">Den gemensamma innehållsroten är aktiverad. Appen läser de nya sökvägarna från content/.capmap-config.json.</p><a class="btn btn--primary" href="index.php">Öppna editorn</a>';
} else {
  $content .= '<p>Verktyget läser kartkatalogerna från appens konfiguration och kopierar hela katalogerna med allt innehåll. Kartornas nycklar och visningsnamn behålls. Efter verifiering och aktivering tas originalfilerna och tomma undermappar bort. De monterade källkatalogerna, exempelvis /content2, lämnas alltid kvar.</p><ol><li>Säkerhetskopiera samtliga kartkataloger och config innan du börjar.</li><li>Kör under ett underhållsfönster utan andra användare, redigeringar eller importer. Sidor kan tillfälligt vara otillgängliga under flytten.</li><li>Kontrollera sökvägarna nedan. PHP behöver läsrättighet till källfilerna och skrivrättighet inne i källkatalogerna och /content för att kunna städa originalen. Ingen root-användare eller skrivrättighet till appens rot, config eller storage behövs. /content måste redan finnas, exempelvis som en monterad volym, och ha plats för en extra kopia av allt kartinnehåll.</li><li>Tryck på knappen en gång och vänta tills resultatet visas.</li></ol><p>/content kopieras till /content/content utan att källan tas bort eller kopieras in i sig själv. Filerna verifieras med SHA-256. Appen växlar till de nya sökvägarna först när kopieringen är klar och tar därefter bort verifierade originalfiler. Original som inte kan tas bort lämnas kvar och visas som varningar. Vid fel före aktiveringen tas de nya kopiorna bort. Vid serveravbrott finns en journal i /content/.capmap-migration/plan.json. Om avbrottet skedde före aktivering: kontrollera journalen och ta bort endast ofullständiga kopior innan ett nytt försök. Om content/.capmap-config.json redan finns: de nya kartorna är aktiva; behåll dem och kontrollera eventuella återstående original manuellt. Monteringspunkter tas aldrig bort eller byter namn.</p>';
  if ($plan) {
    $content .= '<div style="overflow:auto"><table><thead><tr><th>Karta</th><th>Från</th><th>Till</th></tr></thead><tbody>';
    foreach ($plan as $key => $entry) $content .= '<tr><td>'.h($entry['label']).'</td><td><code>'.h($entry['source']).'</code></td><td><code>'.h($entry['target']).'</code></td></tr>';
    $content .= '</tbody></table></div>';
    if (!$error) $content .= '<form method="post" style="margin-top:20px">'.csrf_field().'<p><label><input type="checkbox" name="backup" value="yes" required> Jag har en säkerhetskopia och inga andra användare arbetar i appen.</label></p><button class="btn btn--primary" type="submit">Migrera innehållet till /content</button></form>';
  }
}
$content .= '</div></div>';
require __DIR__ . '/../app/templates/layout.php';
