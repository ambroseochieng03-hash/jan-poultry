<?php
declare(strict_types=1);

/*
| POST /cart-add  (AJAX, JSON)
| Adds one unit of a product. Works for signed-in customers and guests (real
| cart) and for visitors with no account (held in the session as a pending
| order until they register, sign in or continue as a guest).
*/
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/PendingCart.php';
require_once __DIR__ . '/../app/controllers/CartController.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jp_cart_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    jp_cart_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!Security::verifyCsrfToken()) {
    jp_cart_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
}
if (Auth::isUser() && !Auth::isCustomer()) {
    jp_cart_json(['success' => false, 'message' => 'Staff accounts cannot place orders.'], 403);
}

$productId = (int) ($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    jp_cart_json(['success' => false, 'message' => 'Choose a product first.'], 422);
}

$stmt = $pdo->prepare('SELECT id, is_available, stock_quantity FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product || (int) $product['is_available'] !== 1) {
    jp_cart_json(['success' => false, 'message' => 'This product is not available right now.'], 404);
}
if ((int) $product['stock_quantity'] <= 0) {
    jp_cart_json(['success' => false, 'message' => 'This product is out of stock.'], 409);
}

/* Visitor without an account: hold the order in the session. */
if (!Auth::isCustomer() && !Auth::isGuest()) {
    $already = PendingCart::items()[$productId] ?? 0;
    if ($already >= (int) $product['stock_quantity']) {
        jp_cart_json(['success' => false, 'message' => 'You already have all the stock we have of this item.'], 409);
    }
    PendingCart::add($productId);
    jp_cart_json([
        'success'       => true,
        'pending'       => true,
        'message'       => 'Added to your order.',
        'pending_count' => PendingCart::count(),
    ]);
}

$controller = new CartController();
$result = $controller->addItem(Auth::userId(), Auth::guestId(), $productId);

if (Auth::isCustomer()) {
    $q = $pdo->prepare('SELECT COALESCE(SUM(ci.quantity), 0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?');
    $q->execute([Auth::userId()]);
} else {
    $q = $pdo->prepare('SELECT COALESCE(SUM(ci.quantity), 0) FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.guest_id = ?');
    $q->execute([Auth::guestId()]);
}

jp_cart_json([
    'success'    => (bool) ($result['success'] ?? false),
    'pending'    => false,
    'message'    => (string) ($result['message'] ?? ''),
    'cart_count' => (int) $q->fetchColumn(),
], !empty($result['success']) ? 200 : 409);
