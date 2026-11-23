<?php
declare(strict_types=1);

/*
| POST /checkout-place  (AJAX, JSON)
| Places the order for the signed-in customer or guest. Every value is checked
| here again, whatever the browser already checked. The order, its items and
| the cart clean-up happen in one transaction, so a failure leaves nothing
| half-written.
*/
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/models/Cart.php';
require_once __DIR__ . '/../app/models/Order.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jp_checkout_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    jp_checkout_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!Auth::isCustomer() && !Auth::isGuest()) {
    jp_checkout_json(['success' => false, 'message' => 'Please sign in or continue as a guest to place your order.', 'redirect' => '/?auth=login'], 401);
}
if (!Security::verifyCsrfToken()) {
    jp_checkout_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
}

$userId  = Auth::userId();
$guestId = Auth::guestId();
$isGuest = Auth::isGuest();

$cartModel  = new Cart();
$orderModel = new Order();
$items      = $cartModel->getItems($userId, $guestId);

if (!$items) {
    jp_checkout_json(['success' => false, 'message' => 'Your cart is empty.', 'redirect' => '/cart'], 409);
}

$total = 0.0;
foreach ($items as $item) {
    $qty = (int) $item['quantity'];
    if ((int) $item['is_available'] !== 1 || $qty < 1 || $qty > (int) $item['stock_quantity']) {
        jp_checkout_json([
            'success'  => false,
            'message'  => 'Some items in your cart are unavailable or low in stock. Please review your cart first.',
            'redirect' => '/cart',
        ], 409);
    }
    $total += (float) $item['price'] * $qty;
}
if ($total <= 0) {
    jp_checkout_json(['success' => false, 'message' => 'There is nothing to order.', 'redirect' => '/cart'], 409);
}

/* ---- validate the form ---- */
$address = trim((string) ($_POST['delivery_address'] ?? ''));
$address = preg_replace('/\s+/u', ' ', $address) ?? '';
$errors  = [];
$len     = mb_strlen($address);
if ($len < 5 || $len > 300) {
    $errors['delivery_address'] = 'Enter a delivery address between 5 and 300 characters.';
}

if ($isGuest) {
    $name  = trim((string) Auth::name());
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = preg_replace('/[\s\-()]/', '', (string) ($_POST['phone'] ?? '')) ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^(?:\+?254|0)[17]\d{8}$/', $phone)) {
        $errors['phone'] = 'Enter a valid Kenyan phone number, for example 0712345678.';
    }
} else {
    $user = $orderModel->getUserDetails($userId);
    if (!$user) {
        jp_checkout_json(['success' => false, 'message' => 'We could not load your account details.'], 404);
    }
    $name  = (string) $user['name'];
    $email = (string) $user['email'];
    $phone = (string) $user['phone'];
}

if ($errors) {
    jp_checkout_json(['success' => false, 'message' => reset($errors), 'errors' => $errors], 422);
}

/* ---- write everything or nothing ---- */
$db = (new Database())->getConnection();
try {
    $db->beginTransaction();

    $orderId = $orderModel->createOrder($userId, $guestId, $name, $email, $phone, $address, $total);
    foreach ($items as $item) {
        $orderModel->addOrderItem($orderId, (int) $item['product_id'], (int) $item['quantity'], (float) $item['price']);
    }

    $cartId = $cartModel->getCartId($userId, $guestId);
    $del = $db->prepare('DELETE FROM cart_items WHERE cart_id = ?');
    $del->execute([$cartId]);

    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('checkout-place failed: ' . $e->getMessage());
    jp_checkout_json(['success' => false, 'message' => 'We could not place your order. Nothing was charged. Please try again.'], 500);
}

jp_checkout_json([
    'success'  => true,
    'order_id' => $orderId,
    'message'  => 'Your order has been received and is now being processed.',
    'redirect' => '/orders',
]);
