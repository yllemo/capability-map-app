<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: text/html; charset=UTF-8');

$app = cfg('app');
$tax = cfg('taxonomy');

// Use selected content directory
$contentDir = get_content_dir();

function slugify(string $s): string {
  $s = mb_strtolower($s, 'UTF-8');
  $s = preg_replace('/[^a-z0-9\s\-åäö]/u', '', $s);
  $s = preg_replace('/\s+/', '-', trim($s));
  return $s ?: 'new-capability';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF protection
  if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    $error = 'CSRF validation failed';
  } else {
    $layer = (string)($_POST['layer'] ?? '');
    $name = trim((string)($_POST['name'] ?? ''));
    $id = trim((string)($_POST['id'] ?? ''));
    $area = trim((string)($_POST['area'] ?? ''));
    $type = (string)($_POST['type'] ?? 'verksamhetsformaga');
    $level = (int)($_POST['level'] ?? 2);

    if ($layer === '' || $name === '' || $id === '') {
      $error = 'Fyll i layer, id och name';
    } else {
      // Check for duplicate ID
      use App\CapabilityRepository;
      $repo = new CapabilityRepository($contentDir);
      $existing = $repo->byId($id);
      if ($existing) {
        $error = 'ID ' . $id . ' används redan av en annan förmåga';
      } else {
        $file = $layer . '/' . slugify($name) . '.md';
        $abs = $contentDir . '/' . $file;
        if (file_exists($abs)) {
          $error = 'Filen finns redan';
        } else {
          $tpl = "---\n";
          $tpl .= "id: {$id}\n";
          $tpl .= "name: " . str_replace("\n", ' ', $name) . "\n";
          $tpl .= "layer: {$layer}\n";
          $tpl .= "area: " . str_replace("\n", ' ', $area) . "\n";
          $tpl .= "level: {$level}\n";
          $tpl .= "type: {$type}\n";
          $tpl .= "description: Kort beskrivning här\n";
          $tpl .= "owner: Roll/Ägare\n";
          $tpl .= "status: planerad\n";
          $tpl .= "maturity: 1\n";
          $tpl .= "criticality: 1\n";
          $tpl .= "updated: " . date('Y-m-d') . "\n";
          $tpl .= "---\n\n";
          $tpl .= "# {$name}\n\nSkriv förmågebeskrivningen här.\n";

          // Ensure UTF-8 encoding without BOM
          $tpl = mb_convert_encoding($tpl, 'UTF-8', 'UTF-8');

          @mkdir(dirname($abs), 0775, true);
          file_put_contents($abs, $tpl, LOCK_EX);

          // Log the creation
          use App\Logger;
          Logger::audit('capability_created', ['file' => $file, 'id' => $id]);

          header('Location: index.php?file=' . rawurlencode($file));
          exit;
        }
      }
    }
  }
}

$title = 'Ny förmåga';
$activeNav = 'editor';

$content = '<div class="card" style="max-width:760px;margin:0 auto"><div class="card__hd"><strong>Skapa ny capability</strong><a class="btn btn--ghost" href="index.php">← Till editor</a></div><div class="card__bd">';
if ($error) $content .= '<div class="badge" style="border-color: color-mix(in srgb, var(--danger) 60%, var(--border)); color: var(--danger); margin-bottom:10px">'.h($error).'</div>';

$content .= '<form method="post" class="grid" style="gap:10px">';
$content .= csrf_field();
$content .= '<div class="grid grid--2" style="gap:10px">';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">ID</label><input class="input" name="id" placeholder="cap-hr-002"></div>';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">Namn</label><input class="input" name="name" placeholder="Ex. Lönehantering"></div>';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">Skikt</label><select class="select" name="layer">';
foreach (($tax['layers'] ?? []) as $k=>$lbl){ $content .= '<option value="'.h($k).'">'.h($lbl).'</option>'; }
$content .= '</select></div>';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">Område</label><input class="input" name="area" placeholder="Ex. HR"></div>';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">Typ</label><select class="select" name="type">';
foreach (($tax['types'] ?? []) as $k=>$lbl){ $content .= '<option value="'.h($k).'">'.h($lbl).'</option>'; }
$content .= '</select></div>';
$content .= '<div><label class="muted" style="display:block;margin-bottom:6px">Level</label><select class="select" name="level"><option>1</option><option selected>2</option><option>3</option></select></div>';
$content .= '</div>';
$content .= '<div style="display:flex;gap:10px;margin-top:6px"><button class="btn btn--primary" type="submit">Skapa</button></div>';
$content .= '</form></div></div>';

ob_start();
require __DIR__ . '/../app/templates/layout.php';
echo ob_get_clean();
