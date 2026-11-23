<?php
declare(strict_types=1);

/*
| POST /auth-ajax  (JSON) - customer sign in, registration and guest session
| for the sign-in modal. action = login | register | guest
*/
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Validator.php';
require_once __DIR__ . '/../app/services/AuthService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jp_auth_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    jp_auth_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!Security::verifyCsrfToken()) {
    jp_auth_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
}
if (Auth::isUser() && !Auth::isCustomer()) {
    jp_auth_json(['success' => false, 'message' => 'Log out of the staff account first.'], 403);
}

/* At most 15 attempts per 10 minutes per browser session, on top of the login lockout. */
$now = time();
$_SESSION['auth_ajax_attempts'] = array_values(array_filter(
    (array) ($_SESSION['auth_ajax_attempts'] ?? []),
    static fn ($t) => (int) $t > $now - 600
));
if (count($_SESSION['auth_ajax_attempts']) >= 15) {
    jp_auth_json(['success' => false, 'message' => 'Too many attempts. Please wait a few minutes and try again.'], 429);
}
$_SESSION['auth_ajax_attempts'][] = $now;

$auth   = new AuthService();
$action = (string) ($_POST['action'] ?? '');

if ($action === 'login') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($email === '' || $password === '') {
        jp_auth_json(['success' => false, 'message' => 'Enter your email and password.'], 422);
    }
    if ($auth->login($email, $password)) {
        if (Auth::role() === 'customer') {
            jp_auth_json(['success' => true, 'redirect' => Auth::landingPath()]);
        }
        $auth->logout();
        jp_auth_json(['success' => false, 'message' => 'Access denied. Customers only.'], 403);
    }
    $message = (string) ($_SESSION['login_error'] ?? 'Invalid email or password.');
    unset($_SESSION['login_error']);
    jp_auth_json(['success' => false, 'message' => $message], 401);
}

if ($action === 'register') {
    $name     = preg_replace('/\s+/u', ' ', trim((string) ($_POST['name'] ?? ''))) ?? '';
    $email    = trim((string) ($_POST['email'] ?? ''));
    $phone    = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!Validator::required($name) || mb_strlen($name) > 100) {
        jp_auth_json(['success' => false, 'message' => 'Enter your name.'], 422);
    }
    if (!Validator::email($email) || strlen($email) > 150) {
        jp_auth_json(['success' => false, 'message' => 'Enter a valid email address.'], 422);
    }
    if (!Validator::phone($phone)) {
        jp_auth_json(['success' => false, 'message' => 'Enter a valid phone number (7 to 20 digits).'], 422);
    }
    if (!Validator::minLength($password, 8) || strlen($password) > 200) {
        jp_auth_json(['success' => false, 'message' => 'Your password must be at least 8 characters.'], 422);
    }
    if ($auth->emailExists($email)) {
        jp_auth_json(['success' => false, 'message' => 'That email already has an account. Sign in instead.'], 409);
    }

    $auth->register(['name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $password]);
    $user = $auth->findByEmail($email);
    if (!$user) {
        jp_auth_json(['success' => false, 'message' => 'Could not create the account. Please try again.'], 500);
    }
    $auth->authenticate($user);
    jp_auth_json(['success' => true, 'redirect' => Auth::landingPath()]);
}

if ($action === 'guest') {
    $name  = preg_replace('/\s+/u', ' ', trim((string) ($_POST['name'] ?? ''))) ?? '';
    $phone = trim((string) ($_POST['phone'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 60 || preg_match("/^[\\p{L}][\\p{L}\\p{M} .'\\-]*$/u", $name) !== 1) {
        jp_auth_json(['success' => false, 'message' => 'Enter a valid name (letters only, 2 to 60 characters).'], 422);
    }
    if (!Validator::phone($phone)) {
        jp_auth_json(['success' => false, 'message' => 'Enter a valid phone number (7 to 20 digits).'], 422);
    }
    $auth->guestLogin($name, $phone);
    session_regenerate_id(true);
    jp_auth_json(['success' => true, 'redirect' => Auth::landingPath()]);
}

jp_auth_json(['success' => false, 'message' => 'Unknown request.'], 400);
