<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/helpers/Flash.php';

require_once __DIR__ . '/../app/services/AuthService.php';

$auth = new AuthService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (

        !isset($_POST['csrf_token'])

        ||

        !Security::verifyCsrfToken(
            $_POST['csrf_token']
        )

    ) {

        Flash::set(

            'error',

            'Your session has expired. Please refresh the page and try again.'

        );

        header(
            'Location: register'
        );

        exit;

    }

    $name =
        trim($_POST['name']);

    $email =
        trim($_POST['email']);

    $phone =
        trim($_POST['phone']);

    $password =
        $_POST['password'];

    if (
        !Validator::required($name) ||
        !Validator::email($email) ||
        !Validator::phone($phone) ||
        !Validator::minLength($password, 8)
    ) {

        Flash::set(
            'error',
            'Please enter valid details.'
        );

        header(
            'Location: register'
        );

        exit;
    }

    if (
        $auth->emailExists($email)
    ) {

        Flash::set(
            'error',
            'Email already exists.'
        );

        header(
            'Location: register'
        );

        exit;
    }

    $auth->register([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'password' => $password
    ]);

    $user =
        $auth->findByEmail(
            $email
        );

    $auth->authenticate($user);   

    Flash::set(
        'success',
        'Welcome to JAN Poultry!'
    );

    header(
        'Location: /dashboard'
    );

    exit;
}


$flash = Flash::get();

$csrfToken =
    Security::generateCsrfToken();

include '../includes/auth-header.php';
?>

<h2 class="auth-form-title">
    Create Account
</h2>

<p class="auth-form-subtitle">
    <marquee>Join JAN Poultry and start ordering premium poultry products.</marquee>
</p>

<form
    method="POST"
    class="auth-form">

    <input
    type="hidden"
    name="csrf_token"
    value="<?= Security::escape($csrfToken) ?>">

    <input
        type="text"
        name="name"
        placeholder="Full Name"
        required>

    <input
        type="email"
        name="email"
        placeholder="Email Address"
        required>

    <input
        type="text"
        name="phone"
        placeholder="Phone Number"
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

        Create Account

    </button>

</form>

<div class="auth-links">

    <a href="login">

        Already have an account?

    </a><br><br>

    <a href="guest-login">
        Continue As Guest
    </a>

</div>

<?php

include
__DIR__ .
'/../includes/auth-response-modal.php';

?>

<?php if ($flash): ?>

<script>

window.addEventListener(

    'DOMContentLoaded',

    function(){

        showAuthResponseModal(

            <?= $flash['type'] === 'success' ? 'true' : 'false' ?>,

            <?= json_encode(
                $flash['type'] === 'success'
                    ? 'Success'
                    : 'Registration Failed'
            ) ?>,

            <?= json_encode($flash['message']) ?>

        );

    }

);

</script>

<?php endif; ?>

<script src="/assets/js/auth-response-modal.js"></script>

<?php
include '../includes/auth-footer.php';
?>