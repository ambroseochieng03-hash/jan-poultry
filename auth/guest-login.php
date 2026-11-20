<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/services/AuthService.php';

$auth = new AuthService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $auth->guestLogin(
        $_POST['name'],
        $_POST['phone']
    );

    header("Location: /dashboard");
    exit;
}

include __DIR__ . '/../includes/auth-header.php';
?>

<h2 class="auth-form-title">
    Continue as Guest
</h2>

<p class="auth-form-subtitle">
    Browse and order without creating an account.
</p>

<form method="POST" class="auth-form">

    <input
        type="text"
        name="name"
        placeholder="Your Name"
        required>

    <input
        type="text"
        name="phone"
        placeholder="Phone Number"
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
include __DIR__ . '/../includes/auth-footer.php';
?>