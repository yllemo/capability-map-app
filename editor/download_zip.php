<?php
require __DIR__ . '/_auth.php';
require_auth();

$key = trim($_GET['key'] ?? '');
if ($key === '') {
  http_response_code(400);
  echo 'Missing directory key';
  exit;
}

// Get content directories
$contentDirs = get_content_dirs();

// Validate key exists
if (!isset($contentDirs[$key])) {
  http_response_code(400);
  echo 'Invalid directory key';
  exit;
}

$dirConfig = $contentDirs[$key];
$contentDir = $dirConfig['path'];
$folderLabel = $dirConfig['label'] ?? $key;

// Check if directory exists
if (!is_dir($contentDir)) {
  http_response_code(404);
  echo 'Directory not found';
  exit;
}

// Create temporary zip file - OpenShift compatible
// Try multiple temp directory options for container environments
$tempDirs = [
  '/tmp',
  sys_get_temp_dir(),
  dirname(__DIR__) . '/storage',
  '/var/tmp'
];

$tempDir = null;
foreach ($tempDirs as $dir) {
  if (is_dir($dir) && is_writable($dir)) {
    $tempDir = $dir;
    break;
  }
}

if (!$tempDir) {
  http_response_code(500);
  echo 'No writable temporary directory found';
  exit;
}

$tempZip = tempnam($tempDir, 'capmap_');
if ($tempZip === false) {
  http_response_code(500);
  echo 'Could not create temporary file';
  exit;
}

unlink($tempZip); // Delete the temp file, we just want the path
$tempZip .= '.zip';

// Create new zip archive
$zip = new ZipArchive();
$zipResult = $zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($zipResult !== true) {
  // Better error reporting for OpenShift debugging
  $errorMsg = 'Could not create ZIP file';
  switch ($zipResult) {
    case ZipArchive::ER_OK: break;
    case ZipArchive::ER_MULTIDISK: $errorMsg .= ' (multi-disk archives not supported)'; break;
    case ZipArchive::ER_RENAME: $errorMsg .= ' (rename error)'; break;
    case ZipArchive::ER_CLOSE: $errorMsg .= ' (close error)'; break;
    case ZipArchive::ER_SEEK: $errorMsg .= ' (seek error)'; break;
    case ZipArchive::ER_READ: $errorMsg .= ' (read error)'; break;
    case ZipArchive::ER_WRITE: $errorMsg .= ' (write error - check permissions)'; break;
    case ZipArchive::ER_CRC: $errorMsg .= ' (CRC error)'; break;
    case ZipArchive::ER_ZIPCLOSED: $errorMsg .= ' (zip closed)'; break;
    case ZipArchive::ER_NOENT: $errorMsg .= ' (no such file)'; break;
    case ZipArchive::ER_EXISTS: $errorMsg .= ' (file exists)'; break;
    case ZipArchive::ER_OPEN: $errorMsg .= ' (can\'t open file)'; break;
    case ZipArchive::ER_TMPOPEN: $errorMsg .= ' (temp file error)'; break;
    case ZipArchive::ER_ZLIB: $errorMsg .= ' (zlib error)'; break;
    case ZipArchive::ER_MEMORY: $errorMsg .= ' (memory error)'; break;
    case ZipArchive::ER_CHANGED: $errorMsg .= ' (entry changed)'; break;
    case ZipArchive::ER_COMPNOTSUPP: $errorMsg .= ' (compression not supported)'; break;
    case ZipArchive::ER_EOF: $errorMsg .= ' (unexpected EOF)'; break;
    case ZipArchive::ER_INVAL: $errorMsg .= ' (invalid argument)'; break;
    case ZipArchive::ER_NOZIP: $errorMsg .= ' (not a zip archive)'; break;
    case ZipArchive::ER_INTERNAL: $errorMsg .= ' (internal error)'; break;
    case ZipArchive::ER_INCONS: $errorMsg .= ' (zip archive inconsistent)'; break;
    case ZipArchive::ER_REMOVE: $errorMsg .= ' (can\'t remove file)'; break;
    case ZipArchive::ER_DELETED: $errorMsg .= ' (entry deleted)'; break;
    default: $errorMsg .= " (unknown error: $zipResult)"; break;
  }
  
  http_response_code(500);
  echo $errorMsg;
  exit;
}

// Recursively add all markdown files
$added = 0;
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($contentDir, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
  if (!$file->isFile()) continue;

  $ext = strtolower($file->getExtension());
  if ($ext !== 'md' && $ext !== 'markdown') continue;

  // Get relative path for the zip archive
  $filePath = $file->getPathname();
  $relativePath = ltrim(str_replace($contentDir, '', $filePath), DIRECTORY_SEPARATOR);
  $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

  // Add file to zip
  if ($zip->addFile($filePath, $relativePath)) {
    $added++;
  }
}

$zip->close();

// Check if any files were added
if ($added === 0) {
  @unlink($tempZip);
  http_response_code(404);
  echo 'No markdown files found in directory';
  exit;
}

// Generate filename with date
$date = date('Y-m-d');
// Sanitize folder label for filename
$safeFolderName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $folderLabel);
$filename = $safeFolderName . '_' . $date . '.zip';

// Send the file
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tempZip));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file and clean up
readfile($tempZip);
@unlink($tempZip);

// Additional cleanup for OpenShift - try to clean any orphaned temp files
if (getenv('OPENSHIFT_BUILD_NAMESPACE') || getenv('KUBERNETES_SERVICE_HOST')) {
  $tempPattern = $tempDir . '/capmap_*';
  foreach (glob($tempPattern) as $orphanFile) {
    if (filemtime($orphanFile) < time() - 3600) { // Older than 1 hour
      @unlink($orphanFile);
    }
  }
}

exit;
