<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/EmailQueue.php';
require_once __DIR__ . '/PushQueue.php';

/**
 * One place that tells people about an order: the in-app notification, the
 * customer's email and, where it matters, the admin's email. Nothing here may
 * ever break the request that called it, so every public method swallows and
 * logs its own errors.
 */
final class OrderNotifier
{
    /** Called right after a customer places an order. */
    public static function placed(int $orderId): void
    {
        try {
            $order = self::order($orderId);
            if (!$order) {
                return;
            }
            $title   = 'We Received Your Order';
            $message = 'Thank you for ordering from JAN Poultry. Your Order #' . $orderId
                . ' is waiting for approval. We will email you again as soon as it is confirmed.';

            self::inApp($order, 'placed', $title, $message);
            EmailQueue::push(
                (string) $order['customer_email'],
                (string) $order['customer_name'],
                $title . ' #' . $orderId,
                self::render($title, $message, $orderId, self::items($orderId), (float) $order['total_price']),
                'order:' . $orderId . ':placed:customer'
            );

            $adminMsg = 'A new order needs approval.' . "\n"
                . 'Customer: ' . $order['customer_name'] . "\n"
                . 'Phone: ' . $order['customer_phone'] . "\n"
                . 'Delivery address: ' . $order['delivery_address'];
            self::toAdmins(
                'New Order #' . $orderId,
                self::render('New Order #' . $orderId, $adminMsg, $orderId, self::items($orderId), (float) $order['total_price'], '/admin/order-view?id=' . $orderId, 'Open the order'),
                'order:' . $orderId . ':placed:admin'
            );
        } catch (Throwable $e) {
            error_log('OrderNotifier::placed failed: ' . $e->getMessage());
        }
    }

    /** Called after the status really changed: accepted, in_transit, completed, rejected, cancelled. */
    public static function statusChanged(int $orderId, string $status): void
    {
        try {
            $order = self::order($orderId);
            if (!$order) {
                return;
            }

            $text = self::statusText($orderId, $status);
            if ($text === null) {
                return;
            }
            [$title, $message] = $text;

            self::inApp($order, $status, $title, $message);
            EmailQueue::push(
                (string) $order['customer_email'],
                (string) $order['customer_name'],
                $title . ' #' . $orderId,
                self::render($title, $message, $orderId, self::items($orderId), (float) $order['total_price']),
                'order:' . $orderId . ':' . $status . ':customer',
                $status === 'completed' ? $orderId : null
            );

            if ($status === 'cancelled') {
                self::toAdmins(
                    'Order #' . $orderId . ' was cancelled',
                    self::render('Order #' . $orderId . ' was cancelled', 'The customer ' . $order['customer_name'] . ' cancelled this order.', $orderId, self::items($orderId), (float) $order['total_price'], '/admin/order-view?id=' . $orderId, 'Open the order'),
                    'order:' . $orderId . ':cancelled:admin'
                );
            }
        } catch (Throwable $e) {
            error_log('OrderNotifier::statusChanged failed: ' . $e->getMessage());
        }
    }

    /** @return array{0:string,1:string}|null */
    private static function statusText(int $id, string $status): ?array
    {
        return match ($status) {
            'accepted' => ['Order Confirmed', 'Thank you for choosing JAN Poultry. Your Order #' . $id . ' has been approved and is now being prepared by our team.'],
            'in_transit' => ['Your Order Is On The Way', 'Great news. Your Order #' . $id . ' is now on its way to your delivery location.'],
            'completed' => ['Delivery Completed', "Your order has been successfully delivered.\nAttached to this email is your official JAN Poultry receipt for record keeping.\nThank you for trusting JAN Poultry. We appreciate your support and look forward to serving you again."],
            'rejected' => ['Order Update', 'Unfortunately, Order #' . $id . ' could not be processed. Please contact JAN Poultry if you need assistance.'],
            'cancelled' => ['Order Cancelled', 'Your Order #' . $id . ' has been cancelled. If this was a mistake, you are welcome to place a new order any time.'],
            default => null,
        };
    }

    private static function order(int $orderId): ?array
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return list<array{name:string,quantity:int,price:float}> */
    private static function items(int $orderId): array
    {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare(
            'SELECT p.name, oi.quantity, oi.price_snapshot
             FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? ORDER BY oi.id'
        );
        $stmt->execute([$orderId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = ['name' => (string) ($r['name'] ?? 'Product'), 'quantity' => (int) $r['quantity'], 'price' => (float) $r['price_snapshot']];
        }
        return $out;
    }

    private static function inApp(array $order, string $type, string $title, string $message): void
    {
        (new Notification())->create(
            isset($order['user_id']) ? (int) $order['user_id'] : null,
            $order['guest_id'] ?? null,
            (int) $order['id'],
            $type,
            $title,
            $message
        );
        PushQueue::add(
            isset($order['user_id']) ? (int) $order['user_id'] : null,
            $order['guest_id'] ?? null,
            $title,
            $message,
            '/orders',
            'order-' . (int) $order['id']
        );
    }

    /** Admin recipients: the contact email in Settings plus every active admin account. */
    private static function toAdmins(string $subject, string $html, string $key): void
    {
        $db = (new Database())->getConnection();
        $emails = [];

        $s = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'contact_email' LIMIT 1");
        $s->execute();
        $contact = trim((string) $s->fetchColumn());
        if ($contact !== '' && strcasecmp($contact, 'admin@janpoultry.com') !== 0) {
            $emails[strtolower($contact)] = 'JAN Poultry';
        }

        $u = $db->query("SELECT name, email FROM users WHERE role IN ('admin','super_admin') AND status = 'active'");
        foreach ($u ? $u->fetchAll(PDO::FETCH_ASSOC) : [] as $r) {
            if (!empty($r['email'])) {
                $emails[strtolower((string) $r['email'])] = (string) $r['name'];
            }
        }

        foreach ($emails as $email => $name) {
            EmailQueue::push($email, $name, $subject, $html, $key . ':' . md5($email));
        }
    }

    /** Branded email body: deep green header, golden accent, order summary. */
    private static function render(string $title, string $message, int $orderId, array $items, float $total, ?string $link = null, string $linkLabel = 'View your orders'): string
    {
        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($items as $i) {
            $rows .= '<tr><td style="padding:8px 0;border-bottom:1px solid #e5efe8;">' . $e($i['name']) . ' &times; ' . (int) $i['quantity']
                . '</td><td style="padding:8px 0;border-bottom:1px solid #e5efe8;text-align:right;">KSh ' . number_format($i['price'] * $i['quantity'], 2) . '</td></tr>';
        }

        $href = APP_URL . ($link ?? '/orders');

        return '<div style="max-width:640px;margin:auto;background:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#10261a;">'
            . '<div style="background:#0b4a24;padding:26px;text-align:center;"><div style="color:#fbbf0f;font-size:24px;font-weight:bold;letter-spacing:1px;">JAN POULTRY</div>'
            . '<div style="color:#d3e8d9;margin-top:6px;font-size:14px;">Fresh from our farm to your door</div></div>'
            . '<div style="padding:28px;"><h2 style="margin:0 0 12px;color:#0b4a24;">' . $e($title) . '</h2>'
            . '<p style="line-height:1.7;color:#444;margin:0 0 18px;">' . nl2br($e($message)) . '</p>'
            . '<div style="background:#f0f7f1;border-left:4px solid #fbbf0f;padding:14px 16px;margin-bottom:16px;">Order number: <strong>#' . $orderId . '</strong></div>'
            . ($rows !== '' ? '<table style="width:100%;border-collapse:collapse;font-size:14px;">' . $rows
                . '<tr><td style="padding:10px 0;font-weight:bold;">Total</td><td style="padding:10px 0;text-align:right;font-weight:bold;">KSh ' . number_format($total, 2) . '</td></tr></table>' : '')
            . '<p style="text-align:center;margin:26px 0 0;"><a href="' . $e($href) . '" style="background:#fbbf0f;color:#2b2200;text-decoration:none;font-weight:bold;padding:12px 26px;border-radius:999px;display:inline-block;">' . $e($linkLabel) . '</a></p></div>'
            . '<div style="background:#072d17;color:#d3e8d9;text-align:center;padding:16px;font-size:13px;">Thank you for choosing JAN Poultry.</div></div>';
    }
}
