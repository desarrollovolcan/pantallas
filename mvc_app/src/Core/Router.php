<?php

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
    ];

    private array $container;

    public function __construct(array $container)
    {
        $this->container = $container;
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function dispatch(?string $uri = null, ?string $method = null): void
    {
        $uri = $this->normalize($uri ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        $handler = $this->routes[$method][$uri] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'Ruta no encontrada';
            return;
        }

        if (is_array($handler) && isset($handler[0], $handler[1])) {
            [$class, $action] = $handler;
            $controller = new $class($this->container);
            $controller->$action();
            return;
        }

        call_user_func($handler);
    }

    private function normalize(string $path): string
    {
        return '/' . trim($path, '/') ?: '/';
    }
}
