<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry - single public entry point (front controller)
|--------------------------------------------------------------------------
| The web server points at /public only. Every request lands here, the path
| is validated, matched against app/config/routes.php, and the matching page
| file (which lives outside the web root) is executed.
*/

define('APP_ROOT', dirname(__DIR__));

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=(), usb=()');

function jp_status_page(int $code, string $file): never
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    readfile(APP_ROOT . '/public/errors/' . $file);
    exit;
}

$jpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!in_array($jpMethod, ['GET', 'HEAD', 'POST'], true)) {
    header('Allow: GET, HEAD, POST');
    jp_status_page(405, '404.html');
}

$jpPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$jpPath = (string) preg_replace('#/{2,}#', '/', $jpPath);

/* Only letters, digits, dash, underscore and slash can ever be a route.
   This also rejects ".php", "..", encoded dots and null bytes outright. */
if ($jpPath === '' || !preg_match('#^/[A-Za-z0-9/_-]*$#', $jpPath)) {
    jp_status_page(404, '404.html');
}

/* /login/ -> /login */
if (strlen($jpPath) > 1 && str_ends_with($jpPath, '/')) {
    $jpQuery = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    header('Location: ' . rtrim($jpPath, '/') . ($jpQuery !== '' ? '?' . $jpQuery : ''), true, 301);
    exit;
}

$jpRoutes = require APP_ROOT . '/app/config/routes.php';
$jpTarget = $jpRoutes[$jpPath] ?? null;

if ($jpTarget === null || !is_file(APP_ROOT . '/' . $jpTarget)) {
    jp_status_page(404, '404.html');
}

/* Legacy code reads these; give it the clean path. */
$_SERVER['PHP_SELF']    = $jpPath;
$_SERVER['SCRIPT_NAME'] = $jpPath;

$jpFile = APP_ROOT . '/' . $jpTarget;
unset($jpMethod, $jpPath, $jpQuery, $jpRoutes, $jpTarget);

require $jpFile;
