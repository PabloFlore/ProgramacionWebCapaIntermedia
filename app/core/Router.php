<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\Contracts\AccessMiddlewareFactoryInterface;
use App\Middleware\Contracts\CorsMiddlewareFactoryInterface;
use App\Middleware\Contracts\CsrfApiMiddlewareInterface;
use App\Middleware\CsrfWebMiddleware;
use App\Middleware\MiddlewareInterface;
use InvalidArgumentException;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
    ];

    private array $accessPolicy;

    public function __construct(
        private readonly Container $container,
        ?array $accessPolicy = null
    ) {
        $this->accessPolicy = $accessPolicy ?? require __DIR__ . '/../config/access.php';
    }

    public function get(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->register('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->register('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->register('PUT', $uri, $action, $middleware);
    }

    public function patch(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->register('PATCH', $uri, $action, $middleware);
    }

    public function delete(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->register('DELETE', $uri, $action, $middleware);
    }

    private function register(string $method, string $uri, callable|array $action, array $middleware): void
    {
        $uri = $this->normalize($uri);
        $middleware = array_merge($middleware, $this->accessMiddleware($method, $uri));

        $this->routes[$method][$uri] = [
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    private function accessMiddleware(string $method, string $uri): array
    {
        $key = $method . ' ' . $uri;
        $factory = $this->container->get(AccessMiddlewareFactoryInterface::class);

        if (!array_key_exists($key, $this->accessPolicy)) {
            error_log('Política de acceso no definida para ' . $key);
            return [$factory->create([])];
        }

        $roles = $this->accessPolicy[$key];

        if ($roles === 'public') {
            return [];
        }

        if ($roles !== null && !is_array($roles)) {
            throw new InvalidArgumentException('Política de acceso inválida para ' . $key);
        }

        return [$factory->create($roles)];
    }

    public function dispatch(string $uri, string $method): void
    {
        $uri = $this->normalize(parse_url($uri, PHP_URL_PATH) ?? '/');
        $method = strtoupper($method);

        if (str_starts_with($uri, '/api/')) {
            $this->runMiddleware(
                [
                    $this->container->get(CorsMiddlewareFactoryInterface::class)->create($method),
                    $this->container->get(CsrfApiMiddlewareInterface::class),
                ],
                $uri,
                function () use ($uri, $method): void {
                    $this->dispatchRoute($uri, $method);
                }
            );
            return;
        }

        $middleware = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            ? [$this->container->get(CsrfWebMiddleware::class)]
            : [];

        $this->runMiddleware($middleware, $uri, function () use ($uri, $method): void {
            $this->dispatchRoute($uri, $method);
        });
    }

    private function dispatchRoute(string $uri, string $method): void
    {
        $match = $this->match($method, $uri);
        if ($match !== null) {
            $this->runMiddleware(
                $match['middleware'],
                $uri,
                function () use ($match): void {
                    $this->callAction($match['action'], $match['params']);
                }
            );
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
            return [
                'action' => $this->routes[$method][$uri]['action'],
                'middleware' => $this->routes[$method][$uri]['middleware'],
                'params' => [],
            ];
        }

        foreach ($this->routes[$method] as $route => $definition) {
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_-]*\}#', '([^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return [
                    'action' => $definition['action'],
                    'middleware' => $definition['middleware'],
                    'params' => $matches,
                ];
            }
        }

        return null;
    }

    private function runMiddleware(array $middleware, string $uri, callable $next): void
    {
        foreach (array_reverse($middleware) as $layer) {
            if (!$layer instanceof MiddlewareInterface) {
                throw new InvalidArgumentException('El middleware debe implementar MiddlewareInterface.');
            }

            $nextLayer = $next;
            $next = static function () use ($layer, $uri, $nextLayer): void {
                $layer->handle($uri, $nextLayer);
            };
        }

        $next();
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
                $controller = $this->container->get($controller);
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
