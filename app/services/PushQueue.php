<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/** Puts a push notification in the outbox. The worker delivers it; this never throws. */
final class PushQueue
{
    public static function add(?int $userId, ?string $guestId, string $title, string $body, string $url = '/orders', ?string $tag = null): void
    {
        if (!$userId && !$guestId) {
            return;
        }
        try {
            $db = (new Database())->getConnection();
            $db->prepare('INSERT INTO push_outbox (user_id, guest_id, title, body, url, tag) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$userId ?: null, $userId ? null : $guestId, mb_substr($title, 0, 120), mb_substr($body, 0, 240), $url, $tag]);
        } catch (Throwable $e) {
            error_log('PushQueue::add failed: ' . $e->getMessage());
        }
    }
}
