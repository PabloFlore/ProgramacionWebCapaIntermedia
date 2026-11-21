<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Helpers\Csrf;

final class CsrfWebMiddleware implements MiddlewareInterface
{
    private const METODOS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(string $uri, callable $next): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (!in_array($method, self::METODOS, true)) {
            $next();
            return;
        }

        $token = Request::header('X-CSRF-Token');

        if ($token === '' && isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }

        if (!Csrf::validate($token)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo '403 Token CSRF inválido o ausente';
            return;
        }

        $next();
    }
}
