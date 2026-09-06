<?php
// Included before output by both map views. Direct links to the new view also
// select it, while links to view/index.php respect the saved preference.
$requestedInterface = $_GET['interface'] ?? null;
$savedInterface = $_COOKIE['capmap_interface'] ?? $_SESSION['capmap_interface'] ?? 'classic';
$chosenInterface = in_array($requestedInterface, ['classic', 'new'], true)
  ? $requestedInterface
  : ($mapInterface === 'new' ? 'new' : ($savedInterface === 'new' ? 'new' : 'classic'));

$_SESSION['capmap_interface'] = $chosenInterface;
if (($_COOKIE['capmap_interface'] ?? null) !== $chosenInterface) {
  setcookie('capmap_interface', $chosenInterface, [
    'expires' => time() + 365 * 24 * 60 * 60,
    'path' => base_path('/'),
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  ]);
}
if ($chosenInterface !== $mapInterface || $requestedInterface !== null) {
  $query = $_GET;
  unset($query['interface']);
  $target = $chosenInterface === 'new' ? 'view/overview.php' : 'view/index.php';
  $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
  header('Location: ' . base_path($target) . ($queryString !== '' ? '?' . $queryString : ''), true, 302);
  exit;
}
