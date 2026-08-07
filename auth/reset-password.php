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
'/../app/services/AuthService.php';

require_once
__DIR__ .
'/../app/services/CustomerPasswordResetService.php';

$passwordResetService =
    new CustomerPasswordResetService();

$authService =
    new AuthService();

$error = '';

if (isset($_SESSION['auth_error'])) {

    $error =
        $_SESSION['auth_error'];

    unset(
        $_SESSION['auth_error']
    );

}

$token =
    trim(
        $_GET['token']
        ?? ''
    );

/*
|--------------------------------------------------------------------------
| Token Must Exist
|--------------------------------------------------------------------------
*/

if ($token === '') {

    $_SESSION['auth_error'] =
        'The password reset link is invalid.';

    header(
        'Location: login.php'
    );

    exit;

}

/*
|--------------------------------------------------------------------------
| Validate Password Reset Token
|--------------------------------------------------------------------------
*/

$result =
    $passwordResetService
        ->validatePasswordResetToken(
            $token
        );

if (!$result['success']) {

    switch ($result['reason']) {

        case 'expired':

            $_SESSION['auth_error'] =
                'Your password reset link has expired. Please request another one.';

            break;

        case 'used':

            $_SESSION['auth_error'] =
                'This password reset link has already been used.';

            break;

        case 'inactive':

            $_SESSION['auth_error'] =
                'Unable to reset the password for this account.';

            break;

        case 'unauthorized':

            $_SESSION['auth_error'] =
                'Invalid password reset request.';

            break;

        default:

            $_SESSION['auth_error'] =
                'The password reset link is invalid.';

            break;

    }

    header(
        'Location: login.php'
    );

    exit;

}

$reset =
    $result['reset'];

Security::generateCsrfToken();    
/*
|--------------------------------------------------------------------------
| Password Reset Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        /*
        ------------------------------------------------
        | CSRF Protection
        ------------------------------------------------
        */

        if (

            !Security::verifyCsrfToken(

                $_POST['csrf_token']
                ?? ''

            )

        ) {

            $_SESSION['auth_error'] =

                'Your session has expired. Please try again.';

            header(

                'Location: reset-password.php?token=' .

                urlencode($token)

            );

            exit;

        }

    $password =
        $_POST['password']
        ?? '';

    $confirmation =
        $_POST['password_confirmation']
        ?? '';

    /*
    ------------------------------------------------
    Passwords must match
    ------------------------------------------------
    */

    if ($password !== $confirmation) {

        $_SESSION['auth_error'] =
            'Passwords do not match.';

        header(
            'Location: reset-password.php?token=' .
            urlencode($token)
        );

        exit;

    }

    /*
    ------------------------------------------------
    Minimum length
    ------------------------------------------------
    */

    if (strlen($password) < 12) {

        $_SESSION['auth_error'] =
            'Password must contain at least 12 characters.';

        header(
            'Location: reset-password.php?token=' .
            urlencode($token)
        );

        exit;

    }

    /*
    ------------------------------------------------
    Update password
    ------------------------------------------------
    */

    $updated =
        $authService->changePassword(

            (int) $reset['user_id'],

            $password

        );

    if (!$updated) {

        $_SESSION['auth_error'] =
            'Unable to update your password. Please try again.';

        header(
            'Location: reset-password.php?token=' .
            urlencode($token)
        );

        exit;

    }

    /*
    ------------------------------------------------
    Mark reset token as used
    ------------------------------------------------
    */

    $passwordResetService
        ->markPasswordResetUsed(

            (int) $reset['id']

        );

    /*
    ------------------------------------------------
    Success
    ------------------------------------------------
    */

    $_SESSION['auth_success'] =

        'Your password has been reset successfully. Please sign in using your new password.';

    header(
        'Location: login.php'
    );

    exit;

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>

        Reset Password - JAN Poultry

    </title>

    <link
    rel="stylesheet"
    href="/jan-poultry/public/assets/css/admin-reset-password.css">

    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
    rel="stylesheet"
    href="/jan-poultry/public/assets/css/auth-response-modal.css">

</head>

<body class="admin-reset-body">

<?php

$error =
    $_SESSION['auth_error']
    ?? '';

unset(
    $_SESSION['auth_error']
);

$success =
    $_SESSION['auth_success']
    ?? '';

unset(
    $_SESSION['auth_success']
);

?>

<?php if ($error): ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function () {

        showAuthResponseModal(

            false,

            'Password Reset Failed',

            <?= json_encode($error) ?>

        );

    }

);

</script>

<?php endif; ?>

<div class="reset-page">

    <div class="reset-card">

        <div class="reset-brand">

            <h1>

                JAN POULTRY

            </h1>

            <p>

                Customer Password Recovery

            </p>

        </div>

        <div class="reset-icon">

            🔐

        </div>

        <h2>

            Reset Your Password

        </h2>

        <p class="reset-description">

            Welcome back,
            <strong>

                <?= htmlspecialchars($reset['name']) ?>

            </strong>.

            <br><br>

            Create a strong new password for your
            JAN Poultry customer account.

        </p>

        <form
        method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= Security::generateCsrfToken() ?>">

            <div class="input-group">

                <label>

                    New Password

                </label>

                <div class="password-wrapper">

                    <input

                        type="password"

                        name="password"

                        id="password"

                        placeholder="Enter your new password"

                        required>

                    <button

                        type="button"

                        class="toggle-password"

                        onclick="togglePassword('password')">

                        👁

                    </button>

                </div>

            </div>

            <div class="input-group">

                <label>

                    Confirm Password

                </label>

                <div class="password-wrapper">

                    <input

                        type="password"

                        name="password_confirmation"

                        id="password_confirmation"

                        placeholder="Confirm your new password"

                        required>

                    <button

                        type="button"

                        class="toggle-password"

                        onclick="togglePassword('password_confirmation')">

                        👁

                    </button>

                </div>

            </div>

            <div class="password-rules">

                <h4>

                    Password Requirements

                </h4>

                <ul>

                    <li id="lengthRule">

                        ❌ At least 12 characters

                    </li>

                    <li id="upperRule">

                        ❌ One uppercase letter

                    </li>

                    <li id="lowerRule">

                        ❌ One lowercase letter

                    </li>

                    <li id="numberRule">

                        ❌ One number

                    </li>

                    <li id="symbolRule">

                        ❌ One special character

                    </li>

                </ul>

            </div>

            <button

                type="submit"

                class="reset-btn">

                Reset Password

            </button>

        </form>

        <a
        href="login.php"
        class="back-login">

            ← Back to Login

        </a>

    </div>

</div>

<script>

function togglePassword(id)
{

    const input =

        document.getElementById(id);

    input.type =

        input.type === "password"

        ? "text"

        : "password";

}

</script>

<?php

include
__DIR__ .
'/../includes/auth-response-modal.php';

?>

<script src="/jan-poultry/public/assets/js/auth-response-modal.js"></script>

<script src="/jan-poultry/public/assets/js/admin-reset-password.js"></script>

<?php if ($success): ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function () {

        showAuthResponseModal(

            true,

            'Password Updated',

            <?= json_encode($success) ?>

        );

    }

);

</script>

<?php endif; ?>

</body>

</html>