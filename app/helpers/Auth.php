<?php

class Auth
{
    /** Where a customer lands after signing in: their cart if they had started an order, otherwise the products page. */
    public static function landingPath(): string
    {
        $merged = (int) ($_SESSION['pending_merged'] ?? 0);
        unset($_SESSION['pending_merged']);

        return $merged > 0 ? '/cart' : '/products';
    }

    public static function check(): bool
    {
        return
            isset($_SESSION['admin']) ||
            isset($_SESSION['customer']) ||
            isset($_SESSION['guest']);
    }

    public static function isGuest(): bool
    {
        return isset($_SESSION['guest']);
    }

    public static function isUser(): bool
    {
        return
            isset($_SESSION['admin']) ||
            isset($_SESSION['customer']);
    }

    public static function role(): ?string
    {
        if (isset($_SESSION['admin'])) {

            return
                $_SESSION['admin']['role']
                ?? null;

        }

        if (isset($_SESSION['customer'])) {

            return
                $_SESSION['customer']['role']
                ?? 'customer';

        }

        return null;
    }

    public static function userId(): ?int
    {
        if (isset($_SESSION['admin'])) {

            return
                $_SESSION['admin']['id']
                ?? null;

        }

        if (isset($_SESSION['customer'])) {

            return
                $_SESSION['customer']['id']
                ?? null;

        }

        return null;
    }

    public static function guestId(): ?string
    {
        return
            $_SESSION['guest']['id']
            ?? null;
    }

    public static function name(): string
    {
        if (isset($_SESSION['admin'])) {

            return
                $_SESSION['admin']['name'];

        }

        if (isset($_SESSION['customer'])) {

            return
                $_SESSION['customer']['name'];

        }

        if (isset($_SESSION['guest'])) {

            return
                $_SESSION['guest']['name'];

        }

        return 'Guest';
    }

    public static function isAdmin(): bool
    {
        return in_array(

            self::role(),

            [
                'admin',
                'super_admin'
            ],

            true

        );
    }

    public static function isSuperAdmin(): bool
    {
        return
            self::role() === 'super_admin';
    }

    public static function isStaff(): bool
    {
        return
            self::role() === 'staff';
    }

    public static function isDelivery(): bool
    {
        return
            self::role() === 'delivery';
    }

    public static function isCustomer(): bool
    {
        return
            self::role() === 'customer';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {

            header(
                'Location: /login'
            );

            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {

            header(
                'Location: /'
            );

            exit;
        }
    }

/** Same as requireAdmin() but answers AJAX callers with JSON instead of a redirect. */
public static function requireAdminJson(): void
{
    if (self::isAdmin()) {
        return;
    }

    http_response_code(self::check() ? 403 : 401);

    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'You are not authorised to do this.',
    ]);

    exit;
}

public static function requireSuperAdmin(): void
    {
        if (!self::isSuperAdmin()) {

            header(
                'Location: /admin/'
            );

            exit;
        }
    }
}