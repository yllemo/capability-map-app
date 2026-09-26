<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$hash = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  $password = $_POST['password'] ?? '';
  if (!is_string($token) || !csrf_verify($token)) {
    $error = 'Ladda om sidan och försök igen.';
  } elseif (!is_string($password) || $password === '' || strlen($password) > 72 || str_contains($password, "\0")) {
    $error = 'Ange ett lösenord på 1–72 byte utan nolltecken. Svenska tecken kan ta mer än en byte.';
  } else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
  }
  unset($password, $_POST['password']);
}
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skapa lösenordshash</title>
  <?php require __DIR__ . '/app/templates/favicon.php'; ?>
  <link rel="stylesheet" href="<?= h(base_path('assets/overview.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_path('assets/admin.css')) ?>">
  <script defer src="<?= h(base_path('assets/app.js')) ?>"></script>
</head>
<body>
<header class="site-header"><div class="brand"><strong>Skapa lösenordshash</strong></div><button type="button" data-theme-toggle aria-label="Växla ljust och mörkt tema">◐</button></header>
<main class="admin-main" style="max-width:760px">
  <section class="card"><div class="card__bd">
    <h1>Lösenordshash</h1>
    <p>Skapa en hash för <code>password_hash</code> i användarens konfiguration i <code>config/auth.php</code>. Inget konto eller lösenord ändras av detta verktyg.</p>
    <?php if ($error !== ''): ?><p class="admin-notice is-error" role="alert"><?= h($error) ?></p><?php endif; ?>
    <form method="post" class="grid" style="gap:16px">
      <?= csrf_field() ?>
      <label for="password">Lösenord<input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-help"></label>
      <p id="password-help">Använd gärna minst 12 tecken. Högst 72 byte. Lösenordet skickas till denna server och sparas inte av verktyget.</p>
      <button class="btn btn--primary" type="submit">Skapa hash</button>
    </form>
    <?php if ($hash !== ''): ?>
      <p role="status">Hashen är skapad. Kopiera hela värdet nedan.</p>
      <label for="hash">Lösenordshash<textarea id="hash" readonly rows="3" spellcheck="false" style="width:100%;padding:12px;background:var(--soft);color:var(--text);border:1px solid var(--line);border-radius:8px"><?= h($hash) ?></textarea></label>
      <p>Exempel i användarens konfiguration:</p>
      <pre style="overflow:auto"><?= h("'password_hash' => '" . $hash . "',") ?></pre>
      <p>Om användaren har ändrats via Admin kan motsvarande inställning i <code>content/.capmap-admin.php</code> ha företräde.</p>
    <?php endif; ?>
  </div></section>
</main>
</body>
</html>
