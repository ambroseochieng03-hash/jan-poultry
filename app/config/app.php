<?php

declare(strict_types=1);

define('APP_NAME', 'JAN Poultry');

define('APP_URL', 'https://petallike-edith-undatable.ngrok-free.dev/jan-poultry');

define(
    'SUPER_ADMIN_EMAIL',
    'ambroseochieng03@gmail.com'
);

define(
    'MAX_ADMINS',
    5
);

define(
    'LOGIN_MAX_ATTEMPTS',
    5
);

define(
    'LOGIN_LOCKOUT_MINUTES',
    10
);

/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/

define(
    'SESSION_LIFETIME',
    0
);

/*
| Inactivity timeout (30 minutes)
*/
define(
    'SESSION_IDLE_TIMEOUT',
    60 * 30
);

/*
| Absolute lifetime (8 hours)
*/
define(
    'SESSION_ABSOLUTE_TIMEOUT',
    60 * 60 * 8
);

define(
    'SESSION_NAME',
    'JANPOULTRYSESSID'
);

define(
    'SESSION_ABSOLUTE_LIFETIME',
    28800
);

define(
    'CSRF_TOKEN_LIFETIME',
    1800
);

define(
    'SESSION_REGENERATE_INTERVAL',
    300
);

define('APP_ENV', 'development'); 

date_default_timezone_set('Africa/Nairobi');

