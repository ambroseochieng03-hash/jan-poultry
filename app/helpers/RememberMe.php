<?php
declare(strict_types=1);

/**
 * Long-lasting customer sign-in ("remember me") using the remember_tokens table.
 * Cookie value = selector:validator. Only a SHA-256 hash of the validator is
 * stored, the validator is rotated on every use, and a wrong validator for a
 * known selector (a sign of theft) deletes that token.
 */
final class RememberMe
{
    private const DAYS = 90;
    private const MAX_TOKENS_PER_USER = 5;

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    }

    public static function cookieName(): string
    {
        return (self::isHttps() ? '__Host-' : '') . 'jp_remember';
    }

    private static function sendCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::cookieName(), $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Create a new token for this customer and set the cookie. */
    public static function issue(PDO $pdo, int $userId): void
    {
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));

        $pdo->prepare(
            'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . self::DAYS . ' DAY))'
        )->execute([$userId, $selector, hash('sha256', $validator)]);

        // Keep only the newest few tokens per user (one per device).
        $pdo->prepare(
            'DELETE FROM remember_tokens WHERE user_id = ? AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM remember_tokens WHERE user_id = ? ORDER BY id DESC LIMIT ' . self::MAX_TOKENS_PER_USER . '
                ) keep
            )'
        )->execute([$userId, $userId]);
        $pdo->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');

        self::sendCookie($selector . ':' . $validator, time() + self::DAYS * 86400);
    }

    /** Returns the customer's users row when the cookie is valid, otherwise null. */
    public static function restore(PDO $pdo): ?array
    {
        $raw = $_COOKIE[self::cookieName()] ?? '';
        if (!is_string($raw) || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $raw, $m)) {
            if ($raw !== '') {
                self::sendCookie('', time() - 3600);
            }
            return null;
        }
        [, $selector, $validator] = $m;

        $stmt = $pdo->prepare(
            'SELECT t.id AS token_id, t.token_hash, u.*
             FROM remember_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.selector = ? AND t.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            self::sendCookie('', time() - 3600);
            return null;
        }
        if (!hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
            self::sendCookie('', time() - 3600);
            return null;
        }
        if ($row['role'] !== 'customer' || $row['status'] !== 'active') {
            $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
            self::sendCookie('', time() - 3600);
            return null;
        }

        // Rotate the validator and extend the lifetime.
        $newValidator = bin2hex(random_bytes(32));
        $pdo->prepare(
            'UPDATE remember_tokens SET token_hash = ?, expires_at = DATE_ADD(NOW(), INTERVAL ' . self::DAYS . ' DAY) WHERE id = ?'
        )->execute([hash('sha256', $newValidator), $row['token_id']]);
        self::sendCookie($selector . ':' . $newValidator, time() + self::DAYS * 86400);

        unset($row['token_id'], $row['token_hash']);
        return $row;
    }

    /** Sign out of this device. */
    public static function forget(PDO $pdo): void
    {
        $raw = $_COOKIE[self::cookieName()] ?? '';
        if (is_string($raw) && preg_match('/^([a-f0-9]{24}):/', $raw, $m)) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$m[1]]);
        }
        self::sendCookie('', time() - 3600);
    }

    /** Sign out of every device (use after a password change). */
    public static function forgetAll(PDO $pdo, int $userId): void
    {
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
    }
}
