<?php

declare(strict_types=1);

/*
| Creates the first super admin on a fresh install (CLI only).
|   php tools/create-super-admin.php
| The password is typed silently and stored as an Argon2id hash.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/config/database.php';

function ask(string $label, bool $secret = false): string
{
    $tty = function_exists('stream_isatty') && stream_isatty(STDIN);

    fwrite(STDERR, $label . ': ');

    if ($secret && $tty) {
        system('stty -echo');
    }

    $value = trim((string) fgets(STDIN));

    if ($secret && $tty) {
        system('stty echo');
        fwrite(STDERR, "\n");
    }

    return $value;
}

function fail(string $message): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

$name  = ask('Full name');
$email = strtolower(ask('Email'));
$phone = ask('Phone');
$pass  = ask('Password (min 12 chars, upper + lower + digit)', true);
$again = ask('Repeat password', true);

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    fail('Name must be 2 to 100 characters.');
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 150) {
    fail('Invalid email.');
}

if (preg_match('/^[0-9+\-\s]{7,20}$/', $phone) !== 1) {
    fail('Invalid phone (7 to 20 digits).');
}

if ($pass !== $again) {
    fail('Passwords do not match.');
}

if (
    strlen($pass) < 12
    || !preg_match('/[a-z]/', $pass)
    || !preg_match('/[A-Z]/', $pass)
    || !preg_match('/[0-9]/', $pass)
) {
    fail('Password must be 12+ characters with upper case, lower case and a digit.');
}

$pdo = (new Database())->getConnection();

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$exists->execute([$email]);

if ($exists->fetch()) {
    fail('A user with that email already exists.');
}

$insert = $pdo->prepare(
    "INSERT INTO users (name, email, phone, password_hash, role, status)
     VALUES (?, ?, ?, ?, 'super_admin', 'active')"
);

$insert->execute([
    $name,
    $email,
    $phone,
    password_hash($pass, PASSWORD_ARGON2ID),
]);

fwrite(STDERR, "Super admin created: {$email}\n");
