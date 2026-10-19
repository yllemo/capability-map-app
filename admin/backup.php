<?php
declare(strict_types=1);
require __DIR__ . '/../editor/_auth.php';
if (current_user() === null) require_auth();
if (!App\Auth::isAdministrator()) access_denied('Administratörsbehörighet krävs.');
header('Cache-Control: no-store');
$temporary = null;
try {
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST'); http_response_code(405); exit('Använd formuläret under Admin → Export/import.');
  }
  if (!$_POST && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) throw new RuntimeException('Uppladdningen överskrider serverns post_max_size (' . ini_get('post_max_size') . ').');
  $csrf = $_POST['csrf_token'] ?? '';
  if (!is_string($csrf) || !csrf_verify($csrf)) throw new RuntimeException('Sessionen har gått ut. Ladda om sidan.');
  $app = cfg('app');
  $backup = new App\ContentBackup($app['content_root'] ?? $app['content_dir'] ?? dirname(__DIR__) . '/content');
  $action = $_POST['action'] ?? '';
  if ($action === 'export') {
    $temporary = tempnam(sys_get_temp_dir(), 'capmap-backup-');
    if ($temporary === false) throw new RuntimeException('Kunde inte skapa temporär backupfil.');
    $backup->export($temporary, get_content_dirs(), App\AdminSettings::read());
    App\Logger::audit('system_backup_exported');
    header('Content-Type: application/zip');
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="capability-map-backup-' . date('Y-m-d-His') . '.zip"');
    header('Content-Length: ' . filesize($temporary));
    session_write_close();
    readfile($temporary);
  } elseif ($action === 'import') {
    if (($_POST['confirmation'] ?? '') !== 'IMPORTERA') throw new RuntimeException('Skriv IMPORTERA för att aktivera backupens kartor och inställningar.');
    if (($_POST['warning_confirmed'] ?? '') !== '1') throw new RuntimeException('Bekräfta varningsdialogen innan importen startas.');
    $existingFiles = $_POST['existing_files'] ?? '';
    if (!is_string($existingFiles) || !in_array($existingFiles, ['keep','overwrite'], true)) throw new RuntimeException('Välj om befintliga filer ska behållas eller skrivas över.');
    $restoreSettings = ($_POST['restore_settings'] ?? '') === '1';
    $upload = $_FILES['backup'] ?? null;
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('Välj en ZIP-fil. Kontrollera även serverns upload_max_filesize (' . ini_get('upload_max_filesize') . ').');
    if (filesize($upload['tmp_name']) > 104857600) throw new RuntimeException('ZIP-filen får vara högst 100 MiB.');
    $result = $backup->restore($upload['tmp_name'], dirname(__DIR__), $existingFiles, $restoreSettings, get_content_dirs());
    App\Logger::audit('system_backup_imported', $result);
    unset($_SESSION['content_dir_key']);
    $_SESSION['backup_notice'] = 'Importen är klar: ' . $result['maps'] . ' kartor och ' . $result['files'] . ' filer i backupen. ' . $result['preserved_files'] . ' filkonflikter behöll befintligt innehåll. ' . ($restoreSettings ? 'Admininställningarna har återställts. Du kan behöva logga in igen med ett konto från backupen.' : 'Befintliga konton och admininställningar har behållits.');
    header('Location: ' . base_path('admin/index.php?tab=backup'), true, 303);
  } else throw new RuntimeException('Okänd backupåtgärd.');
} catch (Throwable $e) {
  $_SESSION['backup_error'] = $e->getMessage();
  header('Location: ' . base_path('admin/index.php?tab=backup'), true, 303);
} finally {
  if (is_string($temporary) && is_file($temporary)) @unlink($temporary);
}
