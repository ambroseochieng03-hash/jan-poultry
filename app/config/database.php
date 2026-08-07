<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

Env::load();

class Database
{
    private static ?PDO $pdo = null;

    public function __construct()
    {
        if (self::$pdo !== null) {
            return;
        }

        $host =
            $_ENV['DB_HOST']
            ?? 'localhost';

        $db =
            $_ENV['DB_NAME']
            ?? '';

        $user =
            $_ENV['DB_USER']
            ?? '';

        $pass =
            $_ENV['DB_PASSWORD']
            ?? '';

        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        self::$pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function getConnection(): PDO
    {
        return self::$pdo;
    }
}