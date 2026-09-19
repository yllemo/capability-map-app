<?php
// Included before output by any view that offers a classic/new interface
// toggle. Direct links to the new view also select it, while plain links
// respect the saved (cookie/session) preference. Callers set $mapInterface
// ('classic'|'new') to say which of the pair they are, and may optionally
// set $mapInterfaceTargets to say which two files that pair is (defaults to
// the main map views so existing callers don't need to change).
$mapInterfaceTargets = $mapInterfaceTargets ?? [
  'classic' => 'view/index.php',
  'new' => 'view/overview.php',
];

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
  $target = $mapInterfaceTargets[$chosenInterface] ?? $mapInterfaceTargets['classic'];
  $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
  header('Location: ' . base_path($target) . ($queryString !== '' ? '?' . $queryString : ''), true, 302);
  exit;
}
