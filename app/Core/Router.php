<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /** @var callable|null */
    public $notFound = null;

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = $method === 'HEAD' ? 'GET' : $method;
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $m)) {
                $handler(...array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
                return;
            }
        }
        $allowed = array_keys(array_filter($this->routes, fn ($r) => $this->matchesAny($r, $path)));
        if ($allowed !== []) {
            header('Allow: ' . implode(', ', $allowed));
            http_response_code(405);
            return;
        }
        if ($this->notFound !== null) {
            ($this->notFound)();
            return;
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Page introuvable']);
    }

    /** @param array<string, callable> $routes */
    private function matchesAny(array $routes, string $path): bool
    {
        foreach (array_keys($routes) as $pattern) {
            if (preg_match('#^' . preg_replace('#\{(\w+)\}#', '[^/]+', $pattern) . '$#', $path)) {
                return true;
            }
        }
        return false;
    }
}
