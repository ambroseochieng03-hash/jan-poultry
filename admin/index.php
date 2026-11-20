<?php

/* require_once __DIR__ . '/../app/helpers/AdminGate.php'; */

/* AdminGate::requireGate(); */

require_once
__DIR__ .
'/../app/config/app.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';



/*
|--------------------------------------------------------------------------
| ADMIN ENTRY POINT
|--------------------------------------------------------------------------
|
| If not logged in:
|     -> Admin Login
|
| If logged in as admin:
|     -> Dashboard
|
*/

if (!Auth::check()) {

    header(
        'Location: /admin/login'
    );

    exit;
}

if (Auth::isAdmin()) {

    header(
        'Location: /admin/dashboard'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Logged in but not an admin
|--------------------------------------------------------------------------
*/

header(
    'Location: /'
);

exit;