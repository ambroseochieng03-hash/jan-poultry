<?php

declare(strict_types=1);

class Session
{
    /**
     * Starts a secure PHP session.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        self::configureCookies();

        session_start();

        self::initialize();

        self::validate();
    }

    /**
     * Configures secure session cookie settings.
     */
    private static function configureCookies(): void
    {
        $isHttps =
            (
                !empty($_SERVER['HTTPS'])
                &&
                $_SERVER['HTTPS'] !== 'off'
            )
            ||
            (
                ($_SERVER['SERVER_PORT'] ?? null) == 443
            );

        // __Host- prefix: the browser only accepts the cookie over HTTPS, for this
        // exact host, with Path=/ and no Domain (cannot be set by a sibling site).
        session_name(($isHttps ? '__Host-' : '') . SESSION_NAME);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');
    }

    /**
     * Initializes session metadata.
     */
    private static function initialize(): void
    {
        $now = time();

        if (!isset($_SESSION['created_at'])) {

            $_SESSION['created_at'] = $now;

        }

        if (!isset($_SESSION['last_regeneration'])) {

            $_SESSION['last_regeneration'] = $now;

        }

        if (!isset($_SESSION['last_activity'])) {

            $_SESSION['last_activity'] = $now;

        }

        if (!isset($_SESSION['fingerprint'])) {

            $_SESSION['fingerprint'] =
                self::generateFingerprint();

        }

        if (!isset($_SESSION['ip_fingerprint'])) {

            $_SESSION['ip_fingerprint'] =
                self::generateIpFingerprint();

        }
    }

    /**
     * Validates the current session.
     */
    private static function validate(): void
    {
        self::validateIdleTimeout();

        self::validateAbsoluteLifetime();

        self::validateFingerprint();

        self::rotateSessionIdIfNeeded();

        $_SESSION['last_activity'] = time();
    }

    /**
     * Expires inactive sessions.
     */
    private static function validateIdleTimeout(): void
    {
        if (
            !isset($_SESSION['last_activity'])
        ) {
            return;
        }

        if (
            time() - $_SESSION['last_activity']
            <= SESSION_IDLE_TIMEOUT
        ) {
            return;
        }

        self::destroy();

        $_SESSION['session_expired'] = true;

        return;
    }

    /**
     * Forces re-authentication after a maximum lifetime.
     */
    private static function validateAbsoluteLifetime(): void
    {
        if (
            !isset($_SESSION['created_at'])
        ) {
            return;
        }

        if (
            time() - $_SESSION['created_at']
            <= SESSION_ABSOLUTE_TIMEOUT
        ) {
            return;
        }

        self::destroy();

        $_SESSION['session_expired'] = true;

        return;
    }

    /**
     * Validates the browser fingerprint.
     */
    private static function validateFingerprint(): void
    {
        if (
            !isset($_SESSION['fingerprint'])
        ) {
            return;
        }

        if (
            hash_equals(
                $_SESSION['fingerprint'],
                self::generateFingerprint()
            )
        ) {
            return;
        }

        self::destroy();

        $_SESSION['session_expired'] = true;

        return;
    }

    /**
     * Rotates the PHP Session ID periodically.
     */
    private static function rotateSessionIdIfNeeded(): void
    {
        if (
            !isset($_SESSION['last_regeneration'])
        ) {
            return;
        }

        if (
            time() - $_SESSION['last_regeneration']
            < SESSION_REGENERATE_INTERVAL
        ) {
            return;
        }

        session_regenerate_id(true);

        $_SESSION['last_regeneration'] =
            time();
    }

    /**
     * Creates a browser fingerprint.
     */
    private static function generateFingerprint(): string
    {
        return hash(
            'sha256',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
    }

    /**
     * Creates a lightweight IP fingerprint.
     */
    private static function generateIpFingerprint(): string
    {
        $ip =
            $_SERVER['REMOTE_ADDR']
            ?? '';

        /*
        |--------------------------------------------------------------------------
        | IPv4
        |--------------------------------------------------------------------------
        |
        | Keep only the first three octets.
        | Example:
        | 102.68.25.17
        | becomes
        | 102.68.25
        |
        */

        if (
            filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            )
        ) {

            $parts =
                explode('.', $ip);

            $ip =
                implode(
                    '.',
                    array_slice($parts, 0, 3)
                );

        }

        /*
        |--------------------------------------------------------------------------
        | IPv6
        |--------------------------------------------------------------------------
        |
        | Keep only the first four groups.
        |
        */

        elseif (

            filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6
            )

        ) {

            $parts =
                explode(':', $ip);

            $ip =
                implode(
                    ':',
                    array_slice($parts, 0, 4)
                );

        }

        return hash(
            'sha256',
            $ip
        );
    }

    /**
     * Completely destroys the current session.
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params =
                session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Validates the trusted database session.
     */
    public static function validateDatabaseSession(
        PDO $pdo
    ): void
    {
        /*
        |--------------------------------------------------------------------------
        | Only authenticated users have trusted sessions
        |--------------------------------------------------------------------------
        */

        if (!isset($_SESSION['admin'])) {
            return;
        }

        $userId =
            $_SESSION['admin']['id'];

        $sessionToken =
            $_SESSION['admin']['session_token'];

        /*
        |--------------------------------------------------------------------------
        | Retrieve the trusted session token
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "
                SELECT session_token
                FROM users
                WHERE id = ?
                LIMIT 1
            "
        );

        $stmt->execute([
            $userId
        ]);

        $user = $stmt->fetch();

        /*
        |--------------------------------------------------------------------------
        | User no longer exists
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            self::destroy();

            $_SESSION['session_expired'] = true;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Session token mismatch
        |--------------------------------------------------------------------------
        */

        if (

            empty($user['session_token'])

            ||

            !hash_equals(
                $user['session_token'],
                $sessionToken
            )

        ) {

            self::destroy();

            $_SESSION['session_expired'] = true;

            return;
        }
    }
}