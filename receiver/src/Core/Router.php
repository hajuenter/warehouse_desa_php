<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable ...$handlers): void
    {
        $this->addRoute('GET', $path, $handlers);
    }

    public function post(string $path, callable ...$handlers): void
    {
        $this->addRoute('POST', $path, $handlers);
    }

    public function put(string $path, callable ...$handlers): void
    {
        $this->addRoute('PUT', $path, $handlers);
    }

    public function delete(string $path, callable ...$handlers): void
    {
        $this->addRoute('DELETE', $path, $handlers);
    }

    private function addRoute(string $method, string $path, array $handlers): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handlers' => $handlers,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->pathToRegex($route['path']);

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );

                $this->runHandlers($route['handlers'], $params);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Route tidak ditemukan']);
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/:([a-zA-Z_]+)/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function runHandlers(array $handlers, array $params): void
    {
        $context = [];

        foreach ($handlers as $index => $handler) {
            $isLast = $index === count($handlers) - 1;
            $result = $handler($params, $context);

            if (!$isLast && is_array($result)) {
                $context = array_merge($context, $result);
            }
        }
    }
}