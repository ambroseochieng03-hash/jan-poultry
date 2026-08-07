<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

Env::load();

return [

    'host' =>
        $_ENV['MAIL_HOST']
        ?? '',

    'port' =>
        (int) (
            $_ENV['MAIL_PORT']
            ?? 587
        ),

    'username' =>
        $_ENV['MAIL_USERNAME']
        ?? '',

    'password' =>
        $_ENV['MAIL_PASSWORD']
        ?? '',

    'encryption' =>
        $_ENV['MAIL_ENCRYPTION']
        ?? 'tls',

    'from_email' =>
        $_ENV['MAIL_FROM_EMAIL']
        ?? '',

    'from_name' =>
        $_ENV['MAIL_FROM_NAME']
        ?? 'JAN Poultry'

];