<?php

declare(strict_types=1);

/*
| Development only (never deployed). Lets PHP's built-in server behave like
| the real web server: static files are served as-is, every other URL goes
| through public/index.php.
|
|   cd <project root>
|   php -S 127.0.0.1:8080 -t public tools/dev-router.php
*/

$root = dirname(__DIR__) . '/public';
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$isStatic = $path !== '/'
    && is_file($root . $path)
    && !preg_match('/\.(php[0-9]?|phtml|phar)$/i', $path)
    && !preg_match('#(^|/)\.#', $path);

if ($isStatic) {
    return false;
}

require $root . '/index.php';
