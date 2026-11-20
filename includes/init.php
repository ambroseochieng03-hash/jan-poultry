<?php

require_once
__DIR__ .
'/../app/config/env.php';

require_once
__DIR__ .
'/../app/config/app.php';

/*
|--------------------------------------------------------------------------
| GLOBAL ERROR & EXCEPTION HANDLER (PRODUCTION SAFE)
|--------------------------------------------------------------------------
*/

if (
    defined('APP_ENV')
    &&
    APP_ENV === 'development'
) {

    ini_set(
        'display_errors',
        '1'
    );

    ini_set(
        'display_startup_errors',
        '1'
    );

    error_reporting(
        E_ALL
    );

} else {

    ini_set(
        'display_errors',
        '0'
    );

    ini_set(
        'display_startup_errors',
        '0'
    );

}

ini_set('log_errors', '0'); // we override logging manually

date_default_timezone_set('Africa/Nairobi');

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

/*
|--------------------------------------------------------------------------
| LOG DIRECTORY SETUP
|--------------------------------------------------------------------------
*/

define('APP_LOG_PATH', __DIR__ . '/../storage/logs');

if (!file_exists(APP_LOG_PATH)) {
    mkdir(APP_LOG_PATH, 0750, true);
}

/*
|--------------------------------------------------------------------------
| LOG WRITER
|--------------------------------------------------------------------------
*/

function jan_log($level, $message, $context = [])
{
    $file = APP_LOG_PATH . '/app-' . date('Y-m-d') . '.log';

    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'level' => $level,
        'message' => $message,
        'context' => $context
    ];

    file_put_contents(
        $file,
        json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND
    );
}

/*
|--------------------------------------------------------------------------
| ERROR HANDLER
|--------------------------------------------------------------------------
*/

set_error_handler(function ($severity, $message, $file, $line) {
    jan_log('ERROR', $message, [
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ]);

    // prevent PHP default output
    return true;
});

/*
|--------------------------------------------------------------------------
| EXCEPTION HANDLER
|--------------------------------------------------------------------------
*/

set_exception_handler(function ($exception) {

    jan_log('EXCEPTION', $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);

    http_response_code(500);

    if (defined('APP_ENV') && APP_ENV === 'development') {

        echo "<h2>Debug Error</h2>";
        echo "<pre>";
        echo $exception->getMessage();
        echo "\n\n";
        echo $exception->getFile() . ":" . $exception->getLine();
        echo "\n\n";
        echo $exception->getTraceAsString();
        echo "</pre>";

    } else {

        readfile(__DIR__ . '/../public/errors/500.html');
    }

    exit;
});

/*
|--------------------------------------------------------------------------
| SHUTDOWN HANDLER (CATCH FATAL ERRORS)
|--------------------------------------------------------------------------
*/

register_shutdown_function(function () {

    $error = error_get_last();

    if (!$error) return;

    jan_log('FATAL', $error['message'], $error);

    http_response_code(500);

    if (defined('APP_ENV') && APP_ENV === 'development') {

        echo "<pre>";
        print_r($error);
        echo "</pre>";

    } else {

        readfile(__DIR__ . '/../public/errors/500.html');
    }
});

require_once
__DIR__ .
'/../app/config/database.php';

require_once
__DIR__ .
'/../app/helpers/Session.php';

require_once
__DIR__ .
'/../app/helpers/Security.php';

Session::start();

$db = new Database();
$pdo = $db->getConnection();

Session::validateDatabaseSession(
    $pdo
);

if (isset($_SESSION['session_expired'])) {

    unset($_SESSION['session_expired']);

    $currentPath =
        $_SERVER['REQUEST_URI'] ?? '';

    if (preg_match('#^/admin(?:/|$|\?)#', $currentPath) === 1) {

        header(
            'Location: /admin/login'
        );

    } else {

        header(
            'Location: /login'
        );

    }

    exit;
}

if (
    isset($_SESSION['admin'])
) {

    $stmt =
    $pdo->prepare(

        "

        SELECT

            id,
            role,
            status

        FROM users

        WHERE id = ?

        LIMIT 1

        "

    );

    $stmt->execute([
        $_SESSION['admin']['id']
    ]);

    $user =
    $stmt->fetch();

    if (
        !$user
        ||
        $user['status'] !== 'active'
    ) {

        Session::destroy();

        Session::start();

        $_SESSION['login_error'] =
            'Login failed. Please try again later.';

        header(
            'Location: /admin/login'
        );

        exit;
    }

    /*
    ----------------------------------
    Keep session role synchronized
    ----------------------------------
    */

    $_SESSION['admin']['role'] =
        $user['role'];

}
Security::generateCsrfToken();  