<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Core\Request;
use App\Services\UserService;

class ApiAdminController extends JsonController
{
    private const ORDENES_USUARIOS = ['usuario_id', 'nombre', 'correo', 'rol', 'bloqueado', 'fecha_registro'];

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function usuarios(): void
    {
        if ($this->requireRole(['admin']) === null) {
            return;
        }

        $rol = Request::string('rol', $_GET);
        $rol = $rol === '' ? null : $rol;
        $bloqueado = null;
        $bloqueadoTexto = Request::string('bloqueado', $_GET);

        if ($bloqueadoTexto !== '') {
            $valor = filter_var($bloqueadoTexto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

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

        $opciones = $this->opcionesListado();

        if ($opciones === null) {
            return;
        }

        $usuarios = $this->userService->listar($rol, $bloqueado, $opciones);
        $total = $this->userService->contarListado($rol, $bloqueado, $opciones['q']);
        $paginas = $total === 0 ? 0 : (int) ceil($total / $opciones['limite']);

        $this->respond([
            'data' => $usuarios,
            'total' => $total,
            'pagina' => $opciones['pagina'],
            'limite' => $opciones['limite'],
            'paginas' => $paginas,
        ], 200);
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

    private function opcionesListado(): ?array
    {
        $q = Request::string('q', $_GET);

        if (mb_strlen($q) > 100) {
            $this->error(400, 'q no debe exceder 100 caracteres');
            return null;
        }

        $orden = Request::string('orden', $_GET, 'usuario_id');
        $orden = $orden === '' ? 'usuario_id' : $orden;

        if (!in_array($orden, self::ORDENES_USUARIOS, true)) {
            $this->error(400, 'orden no es válido');
            return null;
        }

        $direccion = strtolower(Request::string('direccion', $_GET, 'desc'));
        $direccion = $direccion === '' ? 'desc' : $direccion;

        if (!in_array($direccion, ['asc', 'desc'], true)) {
            $this->error(400, 'direccion debe ser asc o desc');
            return null;
        }

        $pagina = Request::int('pagina', $_GET, 1) ?? 1;

        if ($pagina < 1) {
            $this->error(400, 'pagina debe ser mayor o igual a 1');
            return null;
        }

        $limite = Request::int('limite', $_GET, 25) ?? 25;

        if ($limite < 1 || $limite > 100) {
            $this->error(400, 'limite debe estar entre 1 y 100');
            return null;
        }

        return [
            'q' => $q,
            'orden' => $orden,
            'direccion' => $direccion,
            'pagina' => $pagina,
            'limite' => $limite,
        ];
    }
}
