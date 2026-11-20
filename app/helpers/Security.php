<?php

declare(strict_types=1);

class Security
{
    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    | One token per session (synchronizer token pattern). It is NOT burned after
    | a successful request, so several forms, several tabs and AJAX calls all
    | keep working. It rotates every CSRF_TOKEN_LIFETIME seconds (the previous
    | token stays valid for one more period) and after login.
    */

    public static function generateCsrfToken(): string
    {
        if (
            empty($_SESSION['csrf_token'])
            || empty($_SESSION['csrf_generated_at'])
            || (time() - (int) $_SESSION['csrf_generated_at']) > CSRF_TOKEN_LIFETIME
        ) {
            self::regenerateCsrfToken();
        }

        return (string) $_SESSION['csrf_token'];
    }

    public static function regenerateCsrfToken(): void
    {
        if (!empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_previous'] = $_SESSION['csrf_token'];
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_generated_at'] = time();
    }

    /** Drop every token (used on login so an old token can never be reused). */
    public static function resetCsrfToken(): void
    {
        unset($_SESSION['csrf_previous']);

        self::regenerateCsrfToken();

        unset($_SESSION['csrf_previous']);
    }

    /** Token sent by the browser: form field, or X-CSRF-Token header for AJAX. */
    public static function requestToken(): string
    {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        return is_string($token) ? $token : '';
    }

    public static function verifyCsrfToken(mixed $token = null): bool
    {
        $token = $token === null ? self::requestToken() : $token;

        if (!is_string($token) || $token === '') {
            return false;
        }

        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_generated_at'])) {
            return false;
        }

        // Rotates the token if it is due (the old one stays valid in csrf_previous).
        self::generateCsrfToken();

        if (hash_equals((string) $_SESSION['csrf_token'], $token)) {
            return true;
        }

        return !empty($_SESSION['csrf_previous'])
            && hash_equals((string) $_SESSION['csrf_previous'], $token);
    }

    /*
    |--------------------------------------------------------------------------
    | Same-origin check (defence in depth on top of the token)
    |--------------------------------------------------------------------------
    */

    public static function isSameOrigin(): bool
    {
        $source = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($source === '') {
            $source = $_SERVER['HTTP_REFERER'] ?? '';
        }

        // Some privacy settings strip both headers: the CSRF token still decides.
        if ($source === '') {
            return true;
        }

        $host = parse_url($source, PHP_URL_HOST);

        if (!is_string($host) || $host === '') {
            return false;
        }

        $port = parse_url($source, PHP_URL_PORT);

        $sourceAuthority = strtolower($host . ($port ? ':' . $port : ''));

        return hash_equals(
            strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')),
            $sourceAuthority
        );
    }

    /** Valid same-origin POST with a valid token (no redirect, no output). */
    public static function checkPostRequest(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && self::isSameOrigin()
            && self::verifyCsrfToken();
    }

    public static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /**
     * Guard for state-changing handlers: POST only + origin + CSRF token.
     * Browsers are redirected back with a message, AJAX callers get JSON.
     */
    public static function requirePostWithCsrf(string $redirect = '/'): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            if (self::wantsJson()) {
                self::jsonAndExit(405, 'Method not allowed.');
            }

            header('Location: ' . $redirect);
            exit;
        }

        if (self::isSameOrigin() && self::verifyCsrfToken()) {
            return;
        }

        if (self::wantsJson()) {
            self::jsonAndExit(
                419,
                'Your session expired. Please refresh the page and try again.'
            );
        }

        $_SESSION['app_response'] = [
            'success' => false,
            'title'   => 'Security Check Failed',
            'message' => 'Your session expired or the request could not be verified. Please try again.',
        ];

        header('Location: ' . $redirect);
        exit;
    }

    private static function jsonAndExit(int $status, string $message): never
    {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode([
            'success'    => false,
            'message'    => $message,
            'csrf_token' => self::generateCsrfToken(),
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */

    public static function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
