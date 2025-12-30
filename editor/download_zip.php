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

// Create temporary zip file
$tempZip = tempnam(sys_get_temp_dir(), 'capmap_');
unlink($tempZip); // Delete the temp file, we just want the path
$tempZip .= '.zip';

// Create new zip archive
$zip = new ZipArchive();
if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  http_response_code(500);
  echo 'Could not create ZIP file';
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
exit;
