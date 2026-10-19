<?php
require __DIR__ . '/../editor/_auth.php';
if (current_user() === null) { require_auth(); }
if (!App\Auth::isAdministrator()) access_denied('Administratörsbehörighet krävs.');
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
$error = '';
$generatedKey = '';
$tabs = ['site'=>'Webbplats', 'maps'=>'Kartor', 'users'=>'Användare', 'mcp'=>'MCP', 'backup'=>'Export/import'];
$requestedTab = $_GET['tab'] ?? 'site';
$tab = is_string($requestedTab) && isset($tabs[$requestedTab]) ? $requestedTab : 'site';
$field = static function(string $key): string {
  $value = $_POST[$key] ?? '';
  if (!is_string($value)) throw new RuntimeException('Ogiltigt formulärfält.');
  return trim($value);
};
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!csrf_verify($field('csrf_token'))) throw new RuntimeException('Sessionen har gått ut. Ladda om sidan.');
    $action = $field('action');
    $tab = str_starts_with($action, 'mcp_') ? 'mcp' : (in_array($action, ['user', 'delete'], true) ? 'users' : ($action === 'maps' ? 'maps' : 'site'));
    App\AdminSettings::update(function(array $data) use ($field, $action, &$generatedKey): array {
      if ($action === 'settings') {
        $name = $field('site_name');
        $interface = $field('default_interface');
        if ($name === '' || mb_strlen($name) > 100 || !in_array($interface, ['classic','new'], true)) throw new RuntimeException('Kontrollera namn och standardvy.');
        $data['app'] = array_replace($data['app'] ?? [], ['site_name'=>$name, 'default_interface'=>$interface]);
      } elseif ($action === 'maps') {
        $order = $_POST['order'] ?? [];
        $keys = array_keys(get_content_dirs());
        if (!is_array($order) || count($order) !== count($keys)) throw new RuntimeException('Kartlistan har ändrats. Ladda om sidan.');
        foreach ($keys as $key) if (!isset($order[$key]) || !is_string($order[$key]) || !ctype_digit($order[$key])) throw new RuntimeException('Ange ett positivt sorteringsnummer för varje karta.');
        asort($order, SORT_NUMERIC);
        $parents = $_POST['parents'] ?? [];
        if (!is_array($parents)) throw new RuntimeException('Ogiltiga överordnade kartor.');
        $validParents = [];
        foreach ($keys as $key) {
          $parent = $parents[$key] ?? '';
          if (!is_string($parent) || ($parent !== '' && (!in_array($parent, array_map('strval', $keys), true) || $parent === (string)$key))) throw new RuntimeException('Välj en annan befintlig karta som överordnad.');
          if ($parent !== '') $validParents[$key] = $parent;
        }
        foreach ($validParents as $parent) if (isset($validParents[$parent])) throw new RuntimeException('Endast två nivåer tillåts. En överordnad karta får inte själv ha en överordnad karta.');
        $data['app'] = array_replace($data['app'] ?? [], ['map_order'=>array_keys($order), 'map_parents'=>$validParents]);
      } elseif ($action === 'user' || $action === 'delete') {
        $username = $field('username');
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $username)) throw new RuntimeException('Användarnamn måste börja med en liten bokstav och får innehålla små bokstäver, siffror, punkt, bindestreck och understreck.');
        if (in_array($username, ['guest','authenticated'], true)) throw new RuntimeException('Användarnamnet är reserverat.');
        $users = App\Auth::users();
        if ($action === 'delete') {
          if ($username === current_user()) throw new RuntimeException('Du kan inte ta bort ditt eget konto.');
          if ($field('confirm') !== $username) throw new RuntimeException('Bekräfta genom att skriva användarnamnet.');
          if (!isset($users[$username])) throw new RuntimeException('Kontot finns inte.');
          if ($username === 'editor' && (cfg('auth')['editor_password'] ?? '') !== '') throw new RuntimeException('Stäng först av det äldre delade lösenordet i konfigurationen.');
          $data['users'][$username] = null;
        } else {
          $role = $field('role');
          if (!in_array($role, ['admin','editor'], true)) throw new RuntimeException('Ogiltig roll.');
          if ($username === current_user() && $role !== 'admin') throw new RuntimeException('Du kan inte ta bort din egen administratörsroll.');
          $user = $users[$username] ?? [];
          $password = $_POST['password'] ?? '';
          if (!is_string($password)) throw new RuntimeException('Ogiltigt lösenord.');
          if ($password !== '' || !$user) {
            if (strlen($password) < 12 || strlen($password) > 72) throw new RuntimeException('Lösenordet ska vara 12–72 byte långt.');
            $user['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            unset($user['password']);
          }
          $user['name'] = $field('name') ?: $username;
          $user['role'] = $role;
          $user['session_version'] = bin2hex(random_bytes(16));
          $data['users'][$username] = $user;
        }
      } elseif ($action === 'mcp_create') {
        $username = $field('token_user');
        if (!in_array($username, App\Auth::loginableUsers(), true)) throw new RuntimeException('Välj en befintlig användare.');
        if (!App\Acl::editableMaps(get_content_dirs(), $username)) throw new RuntimeException('Användaren behöver redigeringsbehörighet till minst en karta.');
        $generatedKey = bin2hex(random_bytes(32));
        $data['mcp'] = ['token_user'=>$username, 'token_sha256'=>hash('sha256', $generatedKey)];
      } elseif ($action === 'mcp_revoke') {
        $data['mcp'] = ['token_user'=>'', 'token_sha256'=>''];
      } else throw new RuntimeException('Okänd åtgärd.');
      return $data;
    });
    if ($action === 'user' && $field('username') === current_user()) App\Auth::issueCookie(current_user());
    if ($generatedKey === '') { header('Location: ' . base_path('admin/index.php?tab=' . $tab . '&saved=1')); exit; }
  } catch (Throwable $e) { $generatedKey = ''; $error = $e->getMessage(); }
}
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administration · <?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?></title>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/admin.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#admin-panel">Hoppa till inställningarna</a>
<header class="site-header">
  <a class="brand admin-brand" href="<?= h(base_path('view/overview.php')) ?>"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong><?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?></strong><span>Administration</span></div></a>
  <nav aria-label="Vyer och verktyg"><a href="<?= h(base_path('view/overview.php')) ?>">Förmågekarta</a><a href="<?= h(base_path('editor/index.php')) ?>">Editor</a><a href="<?= h(base_path('editor/logout.php')) ?>">Logga ut</a><button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema">◐</button></nav>
</header>
<main class="admin-main">
<section class="intro"><div><p class="eyebrow">ADMINISTRATION</p><h1>Inställningar</h1><p>Hantera webbplatsen, kartorna och användarna.</p></div></section>
<nav class="admin-tabs" aria-label="Inställningskategorier">
<?php foreach ($tabs as $key=>$label): ?><a href="<?= h(base_path('admin/index.php?tab=' . $key)) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= h($label) ?></a><?php endforeach; ?>
</nav>
<div id="admin-panel">
<?php if ($error !== ''): ?><p class="admin-notice is-error" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if (isset($_GET['saved']) && $error === ''): ?><p class="admin-notice" role="status">Inställningarna har sparats.</p><?php endif; ?>
<?php if (isset($_SESSION['backup_error'])): ?><p class="admin-notice is-error" role="alert"><?= h($_SESSION['backup_error']) ?></p><?php unset($_SESSION['backup_error']); endif; ?>
<?php if (isset($_SESSION['backup_notice'])): ?><p class="admin-notice" role="status"><?= h($_SESSION['backup_notice']) ?></p><?php unset($_SESSION['backup_notice']); endif; ?>
<?php if ($tab === 'site'): ?>
<section class="card"><div class="card__bd">
<h2>Webbplats</h2>
<form method="post" class="grid" style="gap:12px">
<?= csrf_field() ?><input type="hidden" name="action" value="settings">
<label>Webbplatsnamn <input class="input" name="site_name" maxlength="100" required value="<?= h(cfg('app')['site_name'] ?? 'Förmågekarta') ?>"></label>
<label>Standardgränssnitt <select class="select" name="default_interface"><?php foreach (['classic'=>'Klassiskt','new'=>'Modernt'] as $key=>$label): ?><option value="<?= $key ?>" <?= (cfg('app')['default_interface'] ?? 'classic') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<p>Standardgränssnitt gäller besökare som inte redan gjort ett eget val.</p>
<button class="btn btn--primary">Spara webbplatsinställningar</button>
</form></div></section>
<?php elseif ($tab === 'maps'): ?>
<section class="card"><div class="card__bd">
<h2>Kartornas ordning och gruppering</h2>
<p>Lägre sorteringsnummer visas först i kartlistorna.</p>
<form method="post" class="grid" style="gap:12px">
<?= csrf_field() ?><input type="hidden" name="action" value="maps">
<?php $position = 0; foreach (get_content_dirs() as $key=>$dir): $position++; ?>
<fieldset class="admin-map"><legend><?= h($dir['label'] ?? $key) ?></legend>
<label><?= h($dir['label'] ?? $key) ?> <input class="input" type="number" min="1" step="1" required name="order[<?= h($key) ?>]" value="<?= $position ?>" style="width:90px"></label>
<label>Överordnad karta för <?= h($dir['label'] ?? $key) ?> <select class="select" name="parents[<?= h($key) ?>]"><option value="">Ingen — huvudnivå</option><?php foreach (get_content_dirs() as $parentKey=>$parentDir): if ($parentKey === $key) continue; ?><option value="<?= h($parentKey) ?>" <?= (cfg('app')['map_parents'][$key] ?? '') === $parentKey ? 'selected' : '' ?>><?= h($parentDir['label'] ?? $parentKey) ?></option><?php endforeach; ?></select></label>
 </fieldset><?php endforeach; ?>
<button class="btn btn--primary">Spara inställningar</button>
<p>Överordnad karta påverkar endast kartväljarnas visning. Underliggande kartor visas indragna efter sin överordnade karta. Högst två nivåer; inga filer flyttas.</p>
</form></div></section>
<?php elseif ($tab === 'backup'): ?>
<section class="card"><div class="card__bd">
<h2>Exportera systembackup</h2>
<p>Hämta alla konfigurerade förmågekartor, övriga filer i innehållsroten och sparade admininställningar som en ZIP-fil. Kartnycklar och länkar mellan förmågor bevaras.</p>
<p>Innehållsrot: <code><?= h(cfg('app')['content_root'] ?? cfg('app')['content_dir'] ?? dirname(__DIR__) . '/content') ?></code></p>
<p>Backupen innehåller även sparade användares lösenordshashar och MCP-nyckelhashar. Förvara filen skyddat. Applikationskod, config/.env och inställningar som enbart ligger i config/ ingår inte. Låsfiler och tillfälliga arbetskataloger undantas.</p>
<?php if (!class_exists(ZipArchive::class)): ?><p class="admin-notice is-error" role="alert">PHP-tillägget ZipArchive (zip) behöver aktiveras på servern för export och import.</p><?php endif; ?>
<form method="post" action="<?= h(base_path('admin/backup.php')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="export"><button class="btn btn--primary" <?= class_exists(ZipArchive::class) ? '' : 'disabled' ?>>Ladda ner ZIP-backup</button></form>
</div></section>
<section class="card"><div class="card__bd">
<h2>Importera systembackup</h2>
<p>Välj en ZIP-backup skapad av denna exportfunktion. Välj uttryckligen hur befintliga filer ska hanteras. Innehållet kontrolleras innan aktivering och en varningsdialog visas före importen.</p>
<p>Ta en aktuell export innan import och utför importen när inga andra användare redigerar. Körbara filer och webbserverkonfiguration stöds inte.</p>
<form id="backup-import-form" method="post" enctype="multipart/form-data" action="<?= h(base_path('admin/backup.php')) ?>" class="grid" style="gap:12px">
<?= csrf_field() ?><input type="hidden" name="action" value="import">
<input type="hidden" name="warning_confirmed" value="">
<label>ZIP-backup <input class="input" type="file" name="backup" accept=".zip,application/zip" required></label>
<p>Max 100 MiB ZIP, 250 MiB uppackat, 25 MiB per fil och 20 000 filer. Serverns gränser: upload_max_filesize <?= h(ini_get('upload_max_filesize')) ?>, post_max_size <?= h(ini_get('post_max_size')) ?>.</p>
<fieldset><legend>Befintliga filer</legend>
<label><input type="radio" name="existing_files" value="keep" checked required> Behåll befintliga filer (standard)</label>
<p>Befintliga kartor och filer behålls. Endast nya filer och kartor läggs till. Vid samma filnamn eller förmåge-ID används den befintliga förmågan.</p>
<label><input type="radio" name="existing_files" value="overwrite" required> Skriv över med backupens innehåll</label>
<p>Backupens kartor blir aktiva och ersätter kartvalet. Rotfiler med samma namn skrivs över. Nuvarande kartkataloger finns kvar på disk men används inte längre.</p>
</fieldset>
<label><input type="checkbox" name="restore_settings" value="1"> Återställ även admininställningar, konton och MCP-nycklar från backupen</label>
<p>Avstängt som standard. Om du väljer detta ersätts sparade inställningar och du kan behöva logga in igen med ett konto från backupen.</p>
<label>Skriv IMPORTERA för att bekräfta <input class="input" name="confirmation" required pattern="IMPORTERA" autocomplete="off"></label>
<button class="btn btn--primary" <?= class_exists(ZipArchive::class) ? '' : 'disabled' ?>>Importera och aktivera backup</button>
</form><noscript><p class="admin-notice is-error">JavaScript måste vara aktiverat för att kunna bekräfta importens varningsdialog.</p></noscript>
<script defer src="<?= h(base_path('assets/admin-backup.js')) ?>"></script>
</div></section>
<?php elseif ($tab === 'mcp'): ?>
<section class="card"><div class="card__bd">
<h2>MCP och API-nyckel</h2>
<p>Läsning via MCP kräver ingen nyckel och följer kartornas läsbehörigheter. Uppdateringar kräver en API-nyckel och den valda användarens redigeringsbehörighet. En webbläsarinloggning räcker inte för uppdateringar via MCP.</p>
<p>Endpoint: <code><?= h(absolute_url('mcp/index.php')) ?></code> · <a href="<?= h(base_path('mcp/test.php')) ?>">Testa MCP</a></p>
<?php if ($generatedKey !== ''): ?>
<div class="admin-notice" role="status"><strong>API-nyckeln har skapats.</strong><p>Kopiera nyckeln nu. Den visas endast här, vid skapandet.</p><label>API-nyckel <input class="input" readonly value="<?= h($generatedKey) ?>" autocomplete="off" spellcheck="false"></label></div>
<?php endif; ?>
<?php $mcpConfig = cfg('mcp'); $keyExists = ($mcpConfig['token_sha256'] ?? '') !== ''; ?>
<p><?= $keyExists ? 'Aktiv nyckel för användare: ' . h($mcpConfig['token_user'] ?? '') : 'Ingen aktiv API-nyckel.' ?></p>
<form method="post" class="grid" style="gap:12px">
<?= csrf_field() ?><input type="hidden" name="action" value="mcp_create">
<label>Användare för nyckeln <select class="select" name="token_user" required>
<?php foreach (App\Auth::loginableUsers() as $username): if (!App\Acl::editableMaps(get_content_dirs(), $username)) continue; ?>
<option value="<?= h($username) ?>" <?= ($mcpConfig['token_user'] ?? current_user()) === $username ? 'selected' : '' ?>><?= h($username) ?></option>
<?php endforeach; ?></select></label>
<p>En ny nyckel ersätter den tidigare. Endast nyckelns hash sparas på servern.</p>
<button class="btn btn--primary"><?= $keyExists ? 'Skapa ny nyckel och ersätt tidigare' : 'Skapa API-nyckel' ?></button>
</form>
<?php if ($keyExists): ?><form method="post" style="margin-top:16px"><?= csrf_field() ?><input type="hidden" name="action" value="mcp_revoke"><button class="btn">Återkalla API-nyckel</button></form><?php endif; ?>
<p>Skicka nyckeln i klientens HTTP-header: <code>Authorization: Bearer &lt;nyckel&gt;</code>. Läs förmågan med <code>capabilities_read</code> och skicka dess <code>sha256</code> som <code>expected_sha256</code> till <code>capabilities_update</code> tillsammans med karta, ID och uppdaterad Markdown.</p>
</div></section>
<?php elseif ($tab === 'users'): ?>
<h2>Användare</h2><p>Administratörer kan hantera denna sida. Läs- och redigeringsbehörigheter för kartor styrs fortsatt i config/acl.php.</p>
<?php foreach (App\Auth::users() + [''=>[]] as $username=>$user): ?>
<details class="card" style="margin-bottom:12px"><summary style="padding:16px"><?= h($username === '' ? 'Lägg till användare' : ($user['name'] ?? $username) . ' (' . $username . ')') ?></summary><div class="card__bd">
<form method="post" class="grid" style="gap:12px">
<?= csrf_field() ?><input type="hidden" name="action" value="user">
<label>Användarnamn <input class="input" name="username" required value="<?= h($username) ?>" <?= $username !== '' ? 'readonly' : '' ?> autocomplete="off"></label>
<label>Visningsnamn <input class="input" name="name" value="<?= h($user['name'] ?? '') ?>"></label>
<label>Roll <select class="select" name="role"><?php foreach (['editor'=>'Editor','admin'=>'Administratör'] as $role=>$label): ?><option value="<?= $role ?>" <?= ($user['role'] ?? (in_array($username, ['admin','editor'], true) ? 'admin' : 'editor')) === $role ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label>
<label><?= $username === '' ? 'Lösenord' : 'Nytt lösenord (lämna tomt för att behålla)' ?> <input class="input" type="password" name="password" autocomplete="new-password" <?= $username === '' ? 'required' : '' ?>></label>
<p>12–72 byte. När kontot sparas behöver användaren logga in igen.</p><button class="btn btn--primary">Spara användare</button>
</form>
<?php if ($username !== '' && $username !== current_user()): ?>
<details><summary>Ta bort användare</summary><p>Kontot tas bort och kan inte längre logga in. Förmågor och filer behålls.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="username" value="<?= h($username) ?>"><label>Skriv <?= h($username) ?> för att bekräfta <input class="input" name="confirm" required></label><button class="btn">Ta bort användare</button></form></details>
<?php endif; ?></div></details>
<?php endforeach; ?>
<?php endif; ?>
</div></main></body></html>
