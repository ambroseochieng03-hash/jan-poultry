<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/services/PasswordResetService.php';

header('Content-Type: application/json');

/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([

        'success' => false,
        'message' => 'Method not allowed.'

    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$token =
    $_POST['csrf_token']
    ?? '';

if (
    !Security::verifyCsrfToken($token)
) {

    http_response_code(403);

    echo json_encode([

        'success' => true,

        'message' =>

            'If an administrator account exists for that email address, a password reset link has been sent.',

        'csrf_token' =>

            Security::generateCsrfToken()

    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| EMAIL
|--------------------------------------------------------------------------
*/

$email = trim(
    $_POST['email']
    ?? ''
);

$email = strtolower($email);

if (
    empty($email)
    ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {

    echo json_encode([

        'success' => false,

        'message' =>
            'Please enter a valid email address.'

    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| SEND RESET LINK
|--------------------------------------------------------------------------
*/

$passwordResetService =
    new PasswordResetService();

$passwordResetService->sendPasswordResetLink(
    $email
);

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'message' =>

        'If an administrator account exists for that email address, a password reset link has been sent.'

]);
