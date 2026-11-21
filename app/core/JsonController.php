<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Auth;

abstract class JsonController extends Controller
{
    protected function respond(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function error(int $status, string $message, array $extra = []): void
    {
        $this->respond(array_merge(['error' => $message], $extra), $status);
    }

    protected function readJsonBody(): ?array
    {
        $longitud = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        if ($longitud > 1048576) {
            return null;
        }

        $cuerpo = file_get_contents('php://input', false, null, 0, 1048577);

        if ($cuerpo === false || strlen($cuerpo) > 1048576) {
            return null;
        }

        $payload = json_decode($cuerpo, true);

        return is_array($payload) ? $payload : null;
    }

    protected function requireAuth(): ?array
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->error(401, 'Se requiere iniciar sesión');
            return null;
        }

        return $usuario;
    }

    protected function requireRole(array $roles): ?array
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return null;
        }

        if (!in_array($usuario['rol'], $roles, true)) {
            $this->error(403, 'No tienes permisos para realizar esta acción');
            return null;
        }

        return $usuario;
    }
}
