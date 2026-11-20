<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/helpers/Flash.php';
require_once __DIR__ . '/../app/services/AuthService.php';

if (Auth::check()) {

    header('Location: /dashboard');

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Security::checkPostRequest()) {

        Flash::set(
            'error',
            'Your session has expired. Please refresh the page and try again.'
        );

        header('Location: guest-login');

        exit;
    }

    /* At most 10 guest sessions per 10 minutes per browser session. */
    $now = time();

    $_SESSION['guest_attempts'] = array_values(
        array_filter(
            (array) ($_SESSION['guest_attempts'] ?? []),
            static fn ($t) => (int) $t > $now - 600
        )
    );

    if (count($_SESSION['guest_attempts']) >= 10) {

        Flash::set(
            'error',
            'Too many attempts. Please wait a few minutes and try again.'
        );

        header('Location: guest-login');

        exit;
    }

    $_SESSION['guest_attempts'][] = $now;

    $name  = preg_replace('/\s+/u', ' ', trim((string) ($_POST['name'] ?? ''))) ?? '';
    $phone = trim((string) ($_POST['phone'] ?? ''));

    if (
        mb_strlen($name) < 2
        || mb_strlen($name) > 60
        || preg_match("/^[\\p{L}][\\p{L}\\p{M} .'\\-]*$/u", $name) !== 1
    ) {

        Flash::set(
            'error',
            'Please enter a valid name (letters only, 2 to 60 characters).'
        );

        header('Location: guest-login');

        exit;
    }

    if (!Validator::phone($phone)) {

        Flash::set(
            'error',
            'Please enter a valid phone number (7 to 20 digits).'
        );

        header('Location: guest-login');

        exit;
    }

    (new AuthService())->guestLogin($name, $phone);

    session_regenerate_id(true);

    Security::resetCsrfToken();

    header('Location: /dashboard');

    exit;
}

$flash = Flash::get();

$csrfToken = Security::generateCsrfToken();

include __DIR__ . '/../includes/auth-header.php';
?>

<h2 class="auth-form-title">
    Continue as Guest
</h2>

<p class="auth-form-subtitle">
    Browse and order without creating an account.
</p>

<form method="POST" class="auth-form" novalidate>

    <input
        type="hidden"
        name="csrf_token"
        value="<?= Security::escape($csrfToken) ?>">

    <input
        type="text"
        name="name"
        placeholder="Your Name"
        minlength="2"
        maxlength="60"
        autocomplete="name"
        required>

    <input
        type="tel"
        name="phone"
        placeholder="Phone Number"
        inputmode="tel"
        maxlength="20"
        autocomplete="tel"
        required>

    <button
        type="submit"
        class="auth-btn">

        Enter as Guest

    </button>

</form>

<div class="auth-links">

    <a href="login">
        Login Instead
    </a>

    <br><br>

    <a href="register">
        Create Account
    </a>

</div>

<?php
include __DIR__ . '/../includes/auth-response-modal.php';
?>

<?php if ($flash): ?>

<script>

window.addEventListener(
    'DOMContentLoaded',
    function () {

        showAuthResponseModal(
            false,
            'Unable To Continue',
            <?= json_encode($flash['message']) ?>
        );

    }
);

</script>

<?php endif; ?>

<script src="/assets/js/auth-response-modal.js"></script>

<?php
include __DIR__ . '/../includes/auth-footer.php';
?>
