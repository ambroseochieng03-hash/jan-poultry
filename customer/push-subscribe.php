<?php
declare(strict_types=1);

/*
| POST /push-subscribe  (JSON)
| Saves or removes this phone's push subscription for the signed-in customer
| or guest. The browser sends the CSRF token in the X-CSRF-Token header.
*/
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/services/WebPush.php';
require_once __DIR__ . '/../app/services/PushQueue.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jp_push_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    jp_push_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!Auth::isCustomer() && !Auth::isGuest()) {
    jp_push_json(['success' => false, 'message' => 'Sign in first.'], 401);
}
if (!Security::verifyCsrfToken()) {
    jp_push_json(['success' => false, 'message' => 'Session expired. Refresh the page.'], 419);
}

$raw = file_get_contents('php://input', false, null, 0, 8192);
$in  = json_decode($raw === false ? '' : $raw, true);
if (!is_array($in)) {
    jp_push_json(['success' => false, 'message' => 'Bad request.'], 400);
}

$userId  = Auth::isCustomer() ? Auth::userId() : null;
$guestId = $userId ? null : Auth::guestId();
$endpoint = (string) ($in['endpoint'] ?? '');
$hash     = hash('sha256', $endpoint);
$db       = (new Database())->getConnection();

if (($in['action'] ?? 'subscribe') === 'unsubscribe') {
    $db->prepare('DELETE FROM push_subscriptions WHERE endpoint_hash = ? AND (user_id <=> ?) AND (guest_id <=> ?)')
        ->execute([$hash, $userId, $guestId]);
    jp_push_json(['success' => true]);
}

$p256dh = (string) ($in['keys']['p256dh'] ?? '');
$auth   = (string) ($in['keys']['auth'] ?? '');
$pubRaw = WebPush::unb64u($p256dh);
if (!WebPush::allowedEndpoint($endpoint) || strlen($pubRaw) !== 65 || $pubRaw[0] !== "\x04" || strlen(WebPush::unb64u($auth)) !== 16) {
    jp_push_json(['success' => false, 'message' => 'This device cannot receive notifications.'], 422);
}

$db->prepare(
    'INSERT INTO push_subscriptions (user_id, guest_id, endpoint, endpoint_hash, p256dh, auth, user_agent)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), guest_id = VALUES(guest_id), p256dh = VALUES(p256dh),
                             auth = VALUES(auth), user_agent = VALUES(user_agent), failures = 0'
)->execute([$userId, $guestId, $endpoint, $hash, $p256dh, $auth, mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);

if (!empty($in['welcome'])) {
    PushQueue::add($userId, $guestId, 'Notifications are on', 'We will tell you when your order is accepted, on the way and delivered.', '/orders', 'welcome');
}

jp_push_json(['success' => true]);
