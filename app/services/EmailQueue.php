<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Puts an email in the outbox. The page never talks to the mail server;
 * workers/email_worker.php does that, so a slow SMTP server cannot freeze a
 * customer's request. Queueing never throws: a failure is logged and ignored.
 */
final class EmailQueue
{
    /**
     * @param string|null $dedupeKey  Same key twice = one email (guards against double clicks and retries).
     * @param int|null    $receiptOrderId  When set, the worker generates this order's PDF receipt and attaches it.
     */
    public static function push(
        string $toEmail,
        string $toName,
        string $subject,
        string $html,
        ?string $dedupeKey = null,
        ?int $receiptOrderId = null
    ): bool {
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare(
                'INSERT IGNORE INTO email_outbox (dedupe_key, to_email, to_name, subject, body, receipt_order_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $dedupeKey,
                $toEmail,
                mb_substr(trim($toName), 0, 150),
                mb_substr($subject, 0, 255),
                $html,
                $receiptOrderId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('EmailQueue::push failed: ' . $e->getMessage());
            return false;
        }
    }
}
