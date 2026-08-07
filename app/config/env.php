<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JAN Poultry
| Environment Loader
|--------------------------------------------------------------------------
|
| Loads variables from the project's .env file.
|
*/

class Env
{
    /**
     * Loaded environment variables.
     */
    private static array $values = [];

    /**
     * Load .env
     */
    public static function load(): void
    {
        if (!empty(self::$values)) {

            return;

        }

        $envFile =
            dirname(__DIR__, 2)
            . '/.env';

        if (!file_exists($envFile)) {

            return;

        }

        $lines =
            file(
                $envFile,
                FILE_IGNORE_NEW_LINES
                |
                FILE_SKIP_EMPTY_LINES
            );

        foreach ($lines as $line) {

            $line = trim($line);

            if (

                $line === ''

                ||

                str_starts_with($line, '#')

            ) {

                continue;

            }

            if (

                !str_contains($line, '=')

            ) {

                continue;

            }

            [$key, $value] =
                explode(
                    '=',
                    $line,
                    2
                );

            self::$values[
                trim($key)
            ] =
                trim($value);

            $key =
            trim($key);

            $value =
                trim($value);

            self::$values[$key] =
                $value;

            $_ENV[$key] =
                $value;

            putenv(
                "$key=$value"
            );    

        }

    }

    /**
     * Get value
     */
    public static function get(

        string $key,

        ?string $default = null

    ): ?string {

        self::load();

        return
            self::$values[$key]
            ??
            $default;

    }

}