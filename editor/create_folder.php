<?php
require __DIR__ . '/_auth.php';
require_auth();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

// CSRF protection
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
  exit;
}

$key = trim($_POST['key'] ?? '');
$label = trim($_POST['label'] ?? '');
$description = trim($_POST['description'] ?? '');

// Validate input
if ($key === '' || $label === '') {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Folder-nyckel och visningsnamn krävs']);
  exit;
}

// Validate key format (only lowercase letters, numbers, underscore, dash)
if (!preg_match('/^[a-z0-9_\-]+$/', $key)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Folder-nyckel får endast innehålla små bokstäver, siffror, - och _']);
  exit;
}

// Check if key already exists
$existingDirs = get_content_dirs();
if (isset($existingDirs[$key])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Folder-nyckeln används redan']);
  exit;
}

// Create the physical directory
$newDirPath = __DIR__ . '/../' . $key;
if (file_exists($newDirPath)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Katalogen finns redan i filsystemet']);
  exit;
}

if (!@mkdir($newDirPath, 0775, true)) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Kunde inte skapa katalog i filsystemet']);
  exit;
}

// Update config/app.php
$configPath = __DIR__ . '/../config/app.php';
$configContent = file_get_contents($configPath);

if ($configContent === false) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Kunde inte läsa config-filen']);
  exit;
}

// Find the content_dirs array and add the new entry
// We'll add it before the closing ];
$newEntry = "    '{$key}' => [\n";
$newEntry .= "      'path' => __DIR__ . '/../{$key}',\n";
$newEntry .= "      'label' => " . var_export($label, true) . ",\n";
$newEntry .= "      'description' => " . var_export($description, true) . ",\n";
$newEntry .= "    ],\n";

// Find the last entry in content_dirs and add after it
// Look for the pattern: content_dirs => [ ... ]
if (preg_match("/'content_dirs'\s*=>\s*\[(.*?)\s*\]/s", $configContent, $matches)) {
  $contentDirsContent = $matches[1];

  // Check if there are any commented examples we should add before
  if (preg_match('/\/\/\s*Example:.*?\/\/\s*\}/s', $contentDirsContent, $commentMatch)) {
    // Add before the commented example
    $updatedContentDirs = str_replace(
      $commentMatch[0],
      $newEntry . '    ' . $commentMatch[0],
      $contentDirsContent
    );
  } else {
    // Just add at the end
    $updatedContentDirs = rtrim($contentDirsContent) . "\n" . $newEntry . '  ';
  }

  $updatedConfig = preg_replace(
    "/'content_dirs'\s*=>\s*\[(.*?)\s*\]/s",
    "'content_dirs' => [\n{$updatedContentDirs}]",
    $configContent,
    1
  );

  // Write back to config file
  if (@file_put_contents($configPath, $updatedConfig, LOCK_EX) === false) {
    // Rollback: delete the directory we created
    @rmdir($newDirPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Kunde inte uppdatera config-filen']);
    exit;
  }

  echo json_encode([
    'success' => true,
    'key' => $key,
    'label' => $label,
    'path' => $newDirPath
  ]);
} else {
  // Rollback: delete the directory we created
  @rmdir($newDirPath);
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Kunde inte hitta content_dirs i config-filen']);
  exit;
}
