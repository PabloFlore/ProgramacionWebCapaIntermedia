<?php

declare(strict_types=1);

namespace App\Middleware;

final class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private int $maxAge;
    private bool $allowCredentials;

    public function __construct(private string $method = 'GET', ?array $config = null)
    {
        $config ??= require __DIR__ . '/../config/cors.php';

        $this->allowedOrigins = $config['allowed_origins'] ?? [];
        $this->allowedMethods = $config['allowed_methods'] ?? [];
        $this->allowedHeaders = $config['allowed_headers'] ?? [];
        $this->maxAge = (int) ($config['max_age'] ?? 600);
        $this->allowCredentials = (bool) ($config['allow_credentials'] ?? true);
    }

    public function handle(string $uri, callable $next): void
    {
        header('Vary: Origin', false);

        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        if (!is_string($origin) || $origin === '') {
            $next();
            return;
        }

        if (!in_array($origin, $this->allowedOrigins, true)) {
            $this->reject();
            return;
        }

        header('Access-Control-Allow-Origin: ' . $origin);

        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        if (strtoupper($this->method) === 'OPTIONS') {
            header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
            header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
            header('Access-Control-Max-Age: ' . $this->maxAge);
            http_response_code(204);
            return;
        }

        $next();
    }

    private function reject(): void
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Origen no permitido'], JSON_UNESCAPED_UNICODE);
    }
}
