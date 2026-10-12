<?php
declare(strict_types=1);

return [
    'dsn'      => ($_ENV['DB_DSN'] ?? '') ?: 'sqlite:' . dirname(__DIR__) . '/data/database.sqlite',
    'user'     => $_ENV['DB_USER'] ?? null,
    'password' => $_ENV['DB_PASSWORD'] ?? null,
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
