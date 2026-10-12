<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/helpers.php';

App\Core\Env::load(ROOT . '/.env');

$isLocal = ($_ENV['APP_ENV'] ?? 'production') === 'local';
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('Europe/Paris');
