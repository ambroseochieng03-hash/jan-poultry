<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Security.php';

require_once
__DIR__ .
'/../app/services/CustomerPasswordResetService.php';

header(
    'Content-Type: application/json'
);

/*
|--------------------------------------------------------------------------
| POST Requests Only
|--------------------------------------------------------------------------
*/

if (

    $_SERVER['REQUEST_METHOD']
    !==
    'POST'

) {

    http_response_code(405);

    echo json_encode([

        'success' => false,

        'message' => 'Method not allowed.'

    ]);

    exit;

}

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

$csrfToken =
    trim(
        $_POST['csrf_token']
        ?? ''
    );

if (

    !Security::verifyCsrfToken(
        $csrfToken
    )

) {

    http_response_code(403);

    echo json_encode([

        'success' => false,

        'message' =>
            'Your session has expired. Please refresh and try again.'

    ]);

    exit;

}

/*
|--------------------------------------------------------------------------
| Validate Email
|--------------------------------------------------------------------------
*/

$email =
    strtolower(

        trim(

            $_POST['email']
            ?? ''

        )

    );

if (

    empty($email)

    ||

    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )

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
| Send Password Reset Email
|--------------------------------------------------------------------------
*/

$service =
    new CustomerPasswordResetService();

$service->sendPasswordResetLink(
    $email
);

/*
|--------------------------------------------------------------------------
| Always Return Success
|--------------------------------------------------------------------------
*/

echo json_encode([

    'success' => true,

    'message' =>

        'If a customer account exists for that email address, a password reset link has been sent.',

    'csrf_token' =>

        Security::generateCsrfToken()

]);

exit;
