<?php
declare(strict_types=1);

/*
| POST /google-auth  (AJAX, JSON)
| Receives the Google ID token from the "Continue with Google" button, checks
| it with Google, then signs in (or creates) the matching customer account.
| Needs GOOGLE_CLIENT_ID in .env.
*/
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/services/AuthService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jp_google_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

$clientId = (string) Env::get('GOOGLE_CLIENT_ID', '');
if ($clientId === '') {
    jp_google_json(['success' => false, 'message' => 'Google sign-in is not set up yet.'], 503);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    jp_google_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!Security::verifyCsrfToken()) {
    jp_google_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
}

/* At most 10 attempts per 10 minutes per browser session. */
$now = time();
$_SESSION['google_attempts'] = array_values(array_filter(
    (array) ($_SESSION['google_attempts'] ?? []),
    static fn ($t) => (int) $t > $now - 600
));
if (count($_SESSION['google_attempts']) >= 10) {
    jp_google_json(['success' => false, 'message' => 'Too many attempts. Please wait a few minutes.'], 429);
}
$_SESSION['google_attempts'][] = $now;

$credential = (string) ($_POST['credential'] ?? '');
if ($credential === '' || strlen($credential) > 4096 || !preg_match('/^[A-Za-z0-9_\-\.]+$/', $credential)) {
    jp_google_json(['success' => false, 'message' => 'Google sign-in failed. Please try again.'], 422);
}

/* Ask Google to verify the token (signature, expiry). */
$ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($credential));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_CONNECTTIMEOUT => 4,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

$claims = is_string($body) ? json_decode($body, true) : null;
if ($code !== 200 || !is_array($claims)) {
    jp_google_json(['success' => false, 'message' => 'Google could not verify your sign-in. Please try again.'], 401);
}

$issuerOk  = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
$audOk     = hash_equals($clientId, (string) ($claims['aud'] ?? ''));
$notExpired = (int) ($claims['exp'] ?? 0) > $now;
$email     = strtolower(trim((string) ($claims['email'] ?? '')));
$verified  = ($claims['email_verified'] ?? '') === 'true' || ($claims['email_verified'] ?? false) === true;
$googleId  = (string) ($claims['sub'] ?? '');

if (!$issuerOk || !$audOk || !$notExpired || !$verified || $googleId === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jp_google_json(['success' => false, 'message' => 'Google sign-in could not be verified.'], 401);
}

$name = trim((string) preg_replace('/\s+/u', ' ', (string) ($claims['name'] ?? '')));
if ($name === '') {
    $name = ucfirst(strstr($email, '@', true) ?: 'Customer');
}
$name = mb_substr($name, 0, 100);

$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? LIMIT 1');
$stmt->execute([$googleId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['role'] !== 'customer') {
            jp_google_json(['success' => false, 'message' => 'Staff accounts must sign in with email and password.'], 403);
        }
        if (!empty($user['google_id']) && $user['google_id'] !== $googleId) {
            jp_google_json(['success' => false, 'message' => 'This email is linked to a different Google account.'], 403);
        }
        $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?')->execute([$googleId, $user['id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role, google_id)
             VALUES (?, ?, NULL, ?, \'customer\', ?)'
        )->execute([$name, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID), $googleId]);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $pdo->lastInsertId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$user || $user['role'] !== 'customer' || $user['status'] !== 'active') {
    jp_google_json(['success' => false, 'message' => 'Sign-in failed. Please try again later.'], 403);
}

(new AuthService())->authenticate($user);
$pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

jp_google_json(['success' => true, 'redirect' => Auth::landingPath()]);
