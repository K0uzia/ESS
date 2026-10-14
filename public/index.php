<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Site\Redirects;
use App\Site\SiteController;

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Une seule URL par contenu : /index.php, slash final manquant et anciennes URL redirigent en 301.
if (in_array($method, ['GET', 'HEAD'], true)) {
    $target = match (true) {
        $path === '/index.php' => '/',
        $path === '/admin' => '/admin/',
        !str_ends_with($path, '/') && isset(SiteController::PAGES[$path . '/']) => $path . '/',
        default => isset(SiteController::PAGES[$path]) || str_starts_with($path, '/admin/') ? null : Redirects::find($path),
    };
    if ($target !== null && $target !== $path) {
        redirect($target, 301);
    }
}

header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('X-Frame-Options: DENY');
header_remove('X-Powered-By');
if (App\Core\Session::isHttps() && ($_SERVER['HTTP_HOST'] ?? '') === 'ess29.org') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (str_starts_with($path, '/admin/')) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    App\Core\Session::start();
}

/** @var App\Core\Router $router */
$router = require __DIR__ . '/../app/routes.php';
$router->dispatch($method, $path);
