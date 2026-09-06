<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');
$error = '';
$plan = [];
$done = get_content_root() !== null;
$migration = new App\ContentMigration(__DIR__ . '/..');
try {
  if (!$done) $plan = $migration->plan(get_content_dirs());
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify((string)($_POST['csrf_token'] ?? ''))) throw new RuntimeException('Ogiltig session. Ladda om sidan.');
    if (!$done) {
      if (($_POST['backup'] ?? '') !== 'yes') throw new RuntimeException('Bekräfta att säkerhetskopian är klar.');
      $migration->run(get_content_dirs());
      $done = true;
    }
  }
} catch (Throwable $e) { $error = $e->getMessage(); }
$title = 'Flytta innehåll';
$activeNav = 'editor';
$content = '<div class="card"><div class="card__hd"><strong>Samla kartorna under /content</strong><a class="btn btn--ghost" href="index.php">Till editor</a></div><div class="card__bd">';
if ($error) $content .= '<p role="alert">'.h($error).'</p>';
if ($done) {
  $content .= '<p role="status">Den gemensamma innehållsroten är aktiverad. Appen läser de nya sökvägarna från config/content.local.json.</p><a class="btn btn--primary" href="index.php">Öppna editorn</a>';
} else {
  $content .= '<p>Verktyget läser kartkatalogerna från appens konfiguration och flyttar hela katalogerna med allt innehåll. Kartornas nycklar och visningsnamn behålls. Appens kodkataloger flyttas inte.</p><ol><li>Säkerhetskopiera samtliga kartkataloger och config innan du börjar.</li><li>Kör under ett underhållsfönster utan andra användare, redigeringar eller importer. Sidor kan tillfälligt vara otillgängliga under flytten.</li><li>Kontrollera sökvägarna nedan. PHP behöver skrivrättighet till appens rot, config och storage.</li><li>Tryck på knappen en gång och vänta tills resultatet visas.</li></ol><p>/content flyttas först till en tillfällig katalog och därefter till /content/content. Den nya konfigurationen aktiveras först när alla kataloger har flyttats. Vid vanliga fel återställs flytten. Vid serveravbrott finns en flyttjournal i storage/content-migration-*/plan.json för manuell återställning.</p>';
  if ($plan) {
    $content .= '<div style="overflow:auto"><table><thead><tr><th>Karta</th><th>Från</th><th>Till</th></tr></thead><tbody>';
    foreach ($plan as $key => $entry) $content .= '<tr><td>'.h($entry['label']).'</td><td><code>'.h($entry['source']).'</code></td><td><code>'.h($entry['target']).'</code></td></tr>';
    $content .= '</tbody></table></div>';
    if (!$error) $content .= '<form method="post" style="margin-top:20px">'.csrf_field().'<p><label><input type="checkbox" name="backup" value="yes" required> Jag har en säkerhetskopia och inga andra användare arbetar i appen.</label></p><button class="btn btn--primary" type="submit">Flytta alla kartkataloger till /content</button></form>';
  }
}
$content .= '</div></div>';
require __DIR__ . '/../app/templates/layout.php';
