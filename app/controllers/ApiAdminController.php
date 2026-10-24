<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Services\UserService;

class ApiAdminController extends JsonController
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function usuarios(): void
    {
        if ($this->requireRole(['admin']) === null) {
            return;
        }

        $rol = isset($_GET['rol']) && is_string($_GET['rol']) ? $_GET['rol'] : null;
        $bloqueado = null;

        if (isset($_GET['bloqueado']) && $_GET['bloqueado'] !== '') {
            $valor = filter_var($_GET['bloqueado'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($valor === null) {
                $this->error(400, 'bloqueado debe ser 1, 0, true o false');
                return;
            }

            $bloqueado = $valor;
        }

        if ($rol !== null && !in_array($rol, ['estudiante', 'instructor', 'admin'], true)) {
            $this->error(400, 'rol debe ser estudiante, instructor o admin');
            return;
        }

        $usuarios = $this->userService->listar($rol, $bloqueado);

        $this->respond(['data' => $usuarios], 200);
    }

    public function bloquear(string $id): void
    {
        $admin = $this->requireRole(['admin']);

        if ($admin === null) {
            return;
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->error(400, 'El identificador del usuario no es válido');
            return;
        }

        $payload = $this->readJsonBody();
        $motivo = is_array($payload) && isset($payload['motivo']) && is_string($payload['motivo'])
            ? $payload['motivo']
            : null;

        $resultado = $this->userService->bloquear((int) $admin['usuario_id'], (int) $id, $motivo);

        if (!$resultado['ok']) {
            $this->error((int) $resultado['status'], (string) $resultado['message']);
            return;
        }

        $this->respond(['mensaje' => $resultado['message']], 200);
    }

    public function reactivar(string $id): void
    {
        if ($this->requireRole(['admin']) === null) {
            return;
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->error(400, 'El identificador del usuario no es válido');
            return;
        }

        $resultado = $this->userService->reactivar((int) $id);

        if (!$resultado['ok']) {
            $this->error((int) $resultado['status'], (string) $resultado['message']);
            return;
        }

        $this->respond(['mensaje' => $resultado['message']], 200);
    }
}
