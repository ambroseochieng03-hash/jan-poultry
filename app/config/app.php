<?php

declare(strict_types=1);

define('APP_NAME', 'JAN Poultry');

require_once __DIR__ . '/env.php';

define('APP_URL', rtrim((string) Env::get('APP_URL', 'http://localhost:8080'), '/'));

define(
    'SUPER_ADMIN_EMAIL',
    (string) Env::get('SUPER_ADMIN_EMAIL', '')
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

/* Registered customers stay signed in much longer than staff (they also get a
   90-day remember-me cookie, see RememberMe). Admin timeouts above are unchanged. */
define('CUSTOMER_SESSION_IDLE_TIMEOUT', 60 * 60 * 24 * 30);
define('CUSTOMER_SESSION_ABSOLUTE_TIMEOUT', 60 * 60 * 24 * 90);

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

define('APP_ENV', Env::get('APP_ENV', 'production') === 'development' ? 'development' : 'production');
date_default_timezone_set('Africa/Nairobi');

