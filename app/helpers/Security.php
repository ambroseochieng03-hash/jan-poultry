<?php

class Security
{
    public static function generateCsrfToken(): string
    {
        /*
        |--------------------------------------------------------------------------
        | Generate a new token if:
        | - none exists
        | - it has expired
        |--------------------------------------------------------------------------
        */

        if (

            !isset($_SESSION['csrf_token'])

            ||

            !isset($_SESSION['csrf_generated_at'])

            ||

            (
                time()
                -
                $_SESSION['csrf_generated_at']
            )
            >
            CSRF_TOKEN_LIFETIME

        ) {

            self::regenerateCsrfToken();

        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(
        string $token
    ): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Token must exist
        |--------------------------------------------------------------------------
        */

        if (

            empty($_SESSION['csrf_token'])

            ||

            empty($_SESSION['csrf_generated_at'])

        ) {

            return false;

        }

        /*
        |--------------------------------------------------------------------------
        | Token has expired
        |--------------------------------------------------------------------------
        */

        if (

            (
                time()
                -
                $_SESSION['csrf_generated_at']
            )
            >
            CSRF_TOKEN_LIFETIME

        ) {

            self::regenerateCsrfToken();

            return false;

        }

        /*
        |--------------------------------------------------------------------------
        | Verify token safely
        |--------------------------------------------------------------------------
        */

        $valid =
            hash_equals(
                $_SESSION['csrf_token'],
                $token
            );

        /*
        |--------------------------------------------------------------------------
        | Successful verification
        |--------------------------------------------------------------------------
        */

        if ($valid) {

            self::regenerateCsrfToken();

        }

        return $valid;
    }

    public static function regenerateCsrfToken(): void
    {
        $_SESSION['csrf_token'] =
            bin2hex(
                random_bytes(32)
            );

        $_SESSION['csrf_generated_at'] =
            time();
    }

    public static function escape(
        string $value
    ): string {

        return htmlspecialchars(
            $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}