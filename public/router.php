<?php
declare(strict_types=1);

// Serveur de développement (php -S) : sert les fichiers existants, le reste passe par index.php.
$file = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($file !== __DIR__ . '/' && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require __DIR__ . '/index.php';
