<?php
require __DIR__ . '/_auth.php';
header('Content-Type: text/html; charset=UTF-8');

use App\Auth;

$isOpen = Auth::isOpen();
$knownUsers = Auth::loginableUsers();
$multiUser = count($knownUsers) > 1 || (count($knownUsers) === 1 && $knownUsers[0] !== 'editor');
$return = ltrim((string)($_GET['return'] ?? $_POST['return'] ?? ''), '/');
// Only allow a plain relative path within this app — never an absolute/external URL.
if (str_contains($return, '://')) $return = '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isOpen) {
  $username = trim((string)($_POST['username'] ?? ($multiUser ? '' : 'editor')));
  $password = (string)($_POST['password'] ?? '');
  $verified = Auth::verifyCredentials($username, $password);
  if ($verified !== null) {
    Auth::issueCookie($verified);
    header('Location: ' . ($return !== '' ? base_path(ltrim($return, '/')) : base_path('editor/index.php')));
    exit;
  }
  $error = 'Fel användarnamn eller lösenord';
}

$siteName = cfg('app')['site_name'] ?? 'Förmågekarta';
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Logga in · <?= h($siteName) ?></title>
  <?php require __DIR__ . '/../app/templates/favicon.php'; ?>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/login.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<a class="skip-link" href="#login">Hoppa till inloggningen</a>
<header class="site-header">
  <a class="brand login-brand" href="<?= h(base_path('view/index.php')) ?>"><span class="brand-symbol" aria-hidden="true">▦</span><div><strong><?= h($siteName) ?></strong><span>Verksamhetens förmågor, samlade</span></div></a>
  <nav aria-label="Vyer och verktyg"><a href="<?= h(base_path('view/index.php')) ?>">Förmågekarta</a><button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema">◐</button></nav>
</header>
<main class="login-main" id="login">
  <section class="login-card" aria-labelledby="login-title">
    <p class="eyebrow">VÄLKOMMEN TILLBAKA</p>
    <h1 id="login-title">Logga in</h1>
    <p class="login-intro">Logga in för att arbeta med dina förmågekartor.</p>
    <?php if ($isOpen): ?>
      <p class="login-notice" role="status"><strong>Inget lösenordsskydd är konfigurerat.</strong> Lägg till konton i <code>/config/auth.php</code> innan driftsättning.</p>
      <a class="login-submit" href="<?= h(base_path('editor/index.php')) ?>">Fortsätt till editorn</a>
    <?php else: ?>
      <?php if (Auth::verifyCredentials('editor', 'CHANGE-ME-TO-SECURE-PASSWORD-BEFORE-DEPLOYMENT') !== null): ?>
        <p class="login-notice" role="alert">Standardlösenordet är fortfarande aktivt. Ändra det i <code>/config/auth.php</code>.</p>
      <?php endif; ?>
      <?php if ($error !== ''): ?><p class="login-notice is-error" id="login-error" role="alert"><?= h($error) ?></p><?php endif; ?>
      <form method="post" class="login-form">
        <?php if ($return !== ''): ?><input type="hidden" name="return" value="<?= h($return) ?>"><?php endif; ?>
        <?php if ($multiUser): ?>
          <label for="username">Användarnamn<input id="username" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= h($username ?? '') ?>" required autofocus></label>
        <?php endif; ?>
        <label for="password">Lösenord<input id="password" name="password" type="password" autocomplete="current-password" required <?= $multiUser ? '' : 'autofocus' ?> <?= $error !== '' ? 'aria-describedby="login-error"' : '' ?>></label>
        <button class="login-submit" type="submit">Logga in</button>
      </form>
    <?php endif; ?>
    <a class="login-back" href="<?= h(base_path('view/index.php')) ?>">← Till förmågekartan</a>
  </section>
</main>
</body>
</html>
