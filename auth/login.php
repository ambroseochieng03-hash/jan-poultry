<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/services/AuthService.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/helpers/Security.php';

$authService =
    new AuthService();

$error = '';

$success = '';

$submittedEmail = '';

if (isset($_SESSION['login_error'])) {

    $error =
        $_SESSION['login_error'];

    unset($_SESSION['login_error']);

} elseif (isset($_SESSION['auth_error'])) {

    $error =
        $_SESSION['auth_error'];

    unset($_SESSION['auth_error']);

}

if (isset($_SESSION['auth_success'])) {

    $success =
        $_SESSION['auth_success'];

    unset($_SESSION['auth_success']);

}

/*
|--------------------------------------------------------------------------
| Already Logged In
|--------------------------------------------------------------------------
*/

$hasAuthMessage =

    !empty($error)

    ||

    !empty($success);

if (

    Auth::check()

    &&

    !$hasAuthMessage

) {

    if (

        Auth::role() === 'customer'

    ) {

        header(
            'Location: /jan-poultry/customer/dashboard.php'
        );

        exit;

    }

}

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (

        !isset($_POST['csrf_token'])

        ||

        !Security::verifyCsrfToken(
            $_POST['csrf_token']
        )

    ) {

        $error =
            'Your session has expired. Please refresh the page and try again.';

    } else {

        $submittedEmail =
            trim(
                $_POST['email']
            );

        $password =
            $_POST['password'];

        if (

            $authService->login(

                $submittedEmail,

                $password

            )

        ) {

            if (

                Auth::role() === 'customer'

            ) {

                header(
                    'Location: /jan-poultry/customer/dashboard.php'
                );

                exit;

            }

            $authService->logout();

            $error =
                'Access denied. Customers only.';

        } else {

            $error =
                'Invalid email or password.';

        }

    }

}

$csrfToken =
    Security::generateCsrfToken();

include
__DIR__ .
'/../includes/auth-header.php';
?>

<h2 class="auth-form-title">
    Welcome Back
</h2>

<p class="auth-form-subtitle">
    Sign in to continue to JAN Poultry.
</p>

<form method="POST" class="auth-form">

    <input
    type="hidden"
    name="csrf_token"
    value="<?= Security::escape($csrfToken) ?>">

    <input
        type="email"
        name="email"
        value="<?= Security::escape($submittedEmail) ?>"
        placeholder="Email Address"
        required>

    <div class="password-group">

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Password"
            required>

        <span
            class="password-toggle"
            onclick="togglePassword('password')">

            👁

        </span>

    </div>

    <button
        type="submit"
        class="auth-btn">

        Login

    </button>

</form>

<div class="auth-links">

    <a
        href="#"
        id="openForgotPasswordModal">

        Forgot Password?

    </a>

    <br><br>

    <a href="register.php">
        Create Account
    </a><br><br>
    
    <a href="guest-login.php">
        Continue As Guest
    </a>

</div>

<?php

include
__DIR__ .
'/../includes/auth-response-modal.php';

?>

<?php

include
__DIR__ .
'/../includes/admin-forgot-password-modal.php';

?>

<?php if ($error): ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function(){

        showAuthResponseModal(

            false,

            'Authentication Failed',

            <?= json_encode($error) ?>

        );

    }

);

</script>

<?php endif; ?>

<?php if ($success): ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function(){

        showAuthResponseModal(

            true,

            'Success',

            <?= json_encode($success) ?>

        );

    }

);

</script>

<?php endif; ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function () {

        const form =

            document.getElementById(
                'forgotPasswordForm'
            );

        if (form) {

            form.action =

                '/jan-poultry/auth/forgot-password-ajax.php';

        }

    }

);

</script>

<script src="/jan-poultry/public/assets/js/auth-response-modal.js"></script>

<script src="/jan-poultry/public/assets/js/admin-forgot-password.js"></script>

<?php
include '../includes/auth-footer.php';
?>