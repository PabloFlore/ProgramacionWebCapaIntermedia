<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, callable|array $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post(string $uri, callable|array $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = $this->normalize(parse_url($uri, PHP_URL_PATH) ?? '/');
        $method = strtoupper($method);

        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];
            $this->callAction($action);
            return;
        }

        foreach ($this->routes[$method] as $route => $action) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_-]*\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $this->callAction($action, $matches);
                return;
            }
        }

        http_response_code(404);

        if (str_starts_with($uri, '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Ruta no encontrada'], JSON_UNESCAPED_UNICODE);
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo '404 Not Found';
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
