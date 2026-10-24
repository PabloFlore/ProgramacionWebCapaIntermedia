<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    public function get(string $uri, callable|array $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function put(string $uri, callable|array $action): void
    {
        $this->routes['PUT'][$this->normalize($uri)] = $action;
    }

    public function patch(string $uri, callable|array $action): void
    {
        $this->routes['PATCH'][$this->normalize($uri)] = $action;
    }

    public function delete(string $uri, callable|array $action): void
    {
        $this->routes['DELETE'][$this->normalize($uri)] = $action;
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = $this->normalize(parse_url($uri, PHP_URL_PATH) ?? '/');
        $method = strtoupper($method);

        $match = $this->match($method, $uri);
        if ($match !== null) {
            $this->callAction($match['action'], $match['params']);
            return;
        }

        $allowed = [];
        foreach (array_keys($this->routes) as $otherMethod) {
            if ($otherMethod !== $method && $this->match($otherMethod, $uri) !== null) {
                $allowed[] = $otherMethod;
            }
        }

        if ($allowed !== []) {
            header('Allow: ' . implode(', ', $allowed));
            $this->sendError(405, 'Método no permitido', str_starts_with($uri, '/api/'));
            return;
        }

        $this->sendError(404, 'Ruta no encontrada', str_starts_with($uri, '/api/'));
    }

    private function match(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        if (isset($this->routes[$method][$uri])) {
            return ['action' => $this->routes[$method][$uri], 'params' => []];
        }

        foreach ($this->routes[$method] as $route => $action) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_-]*\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return ['action' => $action, 'params' => $matches];
            }
        }

        return null;
    }

    private function sendError(int $status, string $message, bool $json): void
    {
        http_response_code($status);

        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo $status . ' ' . $message;
    }

    private function callAction(callable|array $action, array $params = []): void
    {
        if (is_array($action)) {
            [$controller, $method] = $action;
            if (is_string($controller)) {
                $controller = new $controller();
            }
            call_user_func_array([$controller, $method], $params);
            return;
        }

        call_user_func_array($action, $params);
    }

    private function normalize(string $uri): string
    {
        $uri = trim($uri, '/');
        return $uri === '' ? '/' : '/' . $uri;
    }
}
