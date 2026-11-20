<?php

/* require_once __DIR__ . '/../app/helpers/AdminGate.php'; */

/* AdminGate::requireGate(); */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once
__DIR__ .
'/../app/services/PasswordResetService.php';

$authService = new AuthService();

$passwordResetService =
new PasswordResetService();

$error = '';

$lockSeconds = null;
$submittedEmail = '';

$success = '';

if (isset($_SESSION['login_error'])) {

    $error = $_SESSION['login_error'];

    unset($_SESSION['login_error']);

} elseif (isset($_SESSION['auth_error'])) {

    $error = $_SESSION['auth_error'];

    unset($_SESSION['auth_error']);

}

if (
    isset($_SESSION['auth_success'])
) {

    $success =
        $_SESSION['auth_success'];

    unset(
        $_SESSION['auth_success']
    );

}

/*
|--------------------------------------------------------------------------
| Allow authentication messages to be displayed
|--------------------------------------------------------------------------
*/

$hasAuthMessage =

    !empty($error)

    ||

    !empty($success);

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (

    Auth::check()

    &&

    !$hasAuthMessage

) {

    if (Auth::isAdmin()) {
        header('Location: /admin/dashboard');
        exit;
    }

    header('Location: /');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !Security::verifyCsrfToken($_POST['csrf_token'])
    ) {

        $error = 'Your session has expired. Please refresh the page and try again.';

    } else {

        $submittedEmail = trim($_POST['email']);
        $email = $submittedEmail;
        $password = $_POST['password'];

        $success = $authService->login($email, $password);

        $lockSeconds =
            $authService->getRemainingLockTime($email);

        if ($success) {

            // Reload role after login
            $role = Auth::role();

            if ($role === 'admin' || $role === 'super_admin') {

                header('Location: /admin/dashboard');
                exit;
            }

            // block non-admins from admin panel
            $authService->logout();

            $error = "Access denied. Admins only.";

        } else {

            if (
                empty($error)
            ) {

                $error =
                "Invalid email or password.";

            }

        }
    }
}

$csrfToken = Security::generateCsrfToken();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login - JAN POULTRY</title>

    
    <link rel="stylesheet" href="/assets/css/admin-login.css">

    <link rel="stylesheet" href="/assets/css/admin-forgot-password.css">

    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet"
    href="/assets/css/auth-response-modal.css">

</head>

<body class="admin-login-body">

<div class="login-page">

    <div class="login-brand">

        <div class="brand-content">

            <h1>JAN POULTRY</h1>

            <p class="brand-subtitle">
                Smart Poultry Management System
            </p>

            <div class="brand-divider"></div>

            <div class="brand-features">

                <div class="feature">
                    🛡 Secure Administration
                </div>

                <div class="feature">
                    📦 Product Management
                </div>

                <div class="feature">
                    🛒 Order Management
                </div>

                <div class="feature">
                    👥 Customer Management
                </div>

                <div class="feature">
                    🚚 Delivery Tracking
                </div>

            </div>

        </div>

    </div>

    <div class="login-panel">

        <div class="login-box">

            <h2>Welcome Back</h2>

            <p class="login-subtitle">
                Sign in to continue to the Admin Portal
            </p>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= Security::escape($csrfToken) ?>">

                <div class="input-group">

                    <input
                        type="email"
                        name="email"
                        value="<?= Security::escape($submittedEmail) ?>"
                        placeholder="Admin Email"
                        required>

                </div>

                <div class="input-group password-group">

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Password"
                        required>

                    <button
                        type="button"
                        class="toggle-password"
                        onclick="togglePassword()">

                        👁

                    </button>

                </div>

                <div class="login-actions">

                    <a
                        href="#"
                        class="forgot-password"
                        id="openForgotPasswordModal">

                        Forgot Password?

                    </a>

                </div>

                <button
                    type="submit"
                    class="login-btn">

                    Login

                </button>

            </form>


        </div>

    </div>

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
    function () {

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
    function () {

        showAuthResponseModal(

            true,

            'Success',

            <?= json_encode($success) ?>

        );

    }

);

</script>

<?php endif; ?>

<script src="/assets/js/admin-forgot-password.js"></script>

<script>

function togglePassword() {

    const input =
        document.getElementById('password');

    input.type =
        input.type === 'password'
            ? 'text'
            : 'password';

}

</script>

<script src="/assets/js/auth-response-modal.js"></script>

</body>
</html>