<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Helpers\Csrf;
use App\Middleware\Contracts\CsrfApiMiddlewareInterface;

final class CsrfApiMiddleware implements CsrfApiMiddlewareInterface
{
    private const METODOS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(string $uri, callable $next): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (!in_array($method, self::METODOS, true) || !str_starts_with($uri, '/api/')) {
            $next();
            return;
        }

        $site = strtolower(Request::header('Sec-Fetch-Site'));
        $origin = Request::header('Origin');

        if ($site === '' && $origin === '') {
            $next();
            return;
        }

        $token = Request::header('X-CSRF-Token');

        if ($token === '' && isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }

        if (!Csrf::validate($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Token CSRF inválido o ausente'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $next();
    }
}
