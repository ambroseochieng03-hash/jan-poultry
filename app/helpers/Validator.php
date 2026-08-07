<?php

class Validator
{
    public static function required(string $value): bool
    {
        return trim($value) !== '';
    }

    public static function email(string $email): bool
    {
        return filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }

    public static function minLength(
        string $value,
        int $length
    ): bool {
        return mb_strlen($value) >= $length;
    }

    public static function phone(string $phone): bool
    {
        return preg_match(
            '/^[0-9+\-\s]{7,20}$/',
            $phone
        ) === 1;
    }
}