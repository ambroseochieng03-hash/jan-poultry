<?php
declare(strict_types=1);

/**
 * Holds what a visitor without an account chose to order, in their session,
 * and moves it into the real cart the moment they register, sign in or
 * continue as a guest. Nothing is lost by creating an account.
 */
final class PendingCart
{
    private const KEY = 'pending_cart';
    private const MAX_LINES = 30;
    private const MAX_QTY = 50;

    public static function add(int $productId, int $qty = 1): void
    {
        $items = self::items();
        if (!isset($items[$productId]) && count($items) >= self::MAX_LINES) {
            return;
        }
        $items[$productId] = min(self::MAX_QTY, ($items[$productId] ?? 0) + max(1, $qty));
        $_SESSION[self::KEY] = $items;
    }

    /** @return array<int,int> product id => quantity */
    public static function items(): array
    {
        $raw = $_SESSION[self::KEY] ?? [];
        $out = [];
        if (is_array($raw)) {
            foreach ($raw as $id => $qty) {
                if ((int) $id > 0 && (int) $qty > 0) {
                    $out[(int) $id] = (int) $qty;
                }
            }
        }
        return $out;
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    /** Move the pending items into the signed-in customer's or guest's cart. */
    public static function merge(?int $userId, ?string $guestId): int
    {
        $items = self::items();
        if ($items === [] || ($userId === null && $guestId === null)) {
            return 0;
        }
        require_once __DIR__ . '/../services/CartService.php';
        $service = new CartService();
        $merged = 0;
        foreach ($items as $productId => $qty) {
            for ($i = 0; $i < $qty; $i++) {
                $result = $service->addItem($userId, $guestId, $productId);
                if (empty($result['success'])) {
                    break; // out of stock or unavailable: keep what fits
                }
                $merged++;
            }
        }
        self::clear();
        $_SESSION['pending_merged'] = $merged;
        return $merged;
    }
}
