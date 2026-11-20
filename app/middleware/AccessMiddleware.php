<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Helpers\Url;

final class AccessMiddleware implements MiddlewareInterface
{
    public function __construct(private ?array $roles = null)
    {
    }

    public function handle(string $uri, callable $next): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->deny(401, 'Se requiere iniciar sesión', $uri);
            return;
        }

        if ($this->roles !== null && !in_array($usuario['rol'], $this->roles, true)) {
            $this->deny(403, 'No tienes permisos para realizar esta acción', $uri);
            return;
        }

        $next();
    }

    private function deny(int $status, string $message, string $uri): void
    {
        if (str_starts_with($uri, '/api/')) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($status === 401) {
            Session::start();
            $_SESSION['flash'] = ['tipo' => 'error', 'mensaje' => 'Debes iniciar sesión para continuar.'];
            header('Location: ' . Url::to('login'));
            exit;
        }

        http_response_code(403);
        require __DIR__ . '/../views/errors/403.php';
    }
}
