<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\CourseService;

final class ApiCourseController extends JsonController
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    public function detalle(string $id): void
    {
        $cursoId = $this->id($id);

        if ($cursoId === null) {
            $this->error(400, 'El identificador del curso no es válido');
            return;
        }

        $curso = $this->courses->detalle($cursoId);

        if ($curso === null) {
            $this->error(404, 'Curso no encontrado');
            return;
        }

        $this->respond(['data' => $curso]);
    }

    public function gestion(): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $pagina = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 1;
        $limite = isset($_GET['limit']) && ctype_digit((string) $_GET['limit']) ? (int) $_GET['limit'] : 20;
        $instructorId = $usuario['rol'] === 'admin' ? null : (int) ($usuario['usuario_id'] ?? Session::usuarioId());

        $this->respond($this->courses->listarGestion($instructorId, $pagina, $limite));
    }

    public function crear(): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->courses->crear(
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        );

        if (($resultado['ok'] ?? false) === true) {
            $resultado['mensaje'] = $resultado['message'];
            $resultado['curso_id'] = $resultado['curso']['curso_id'] ?? null;
        }

        $this->responder($resultado);
    }

    public function actualizar(string $id): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $cursoId = $this->id($id);
        $payload = $this->readJsonBody();

        if ($cursoId === null || $payload === null) {
            $this->error(400, 'Petición no válida');
            return;
        }

        $this->responder($this->courses->actualizar(
            $cursoId,
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        ));
    }

    public function baja(string $id): void
    {
        $this->cambiarEstatus($id, 'inactivo');
    }

    public function reactivar(string $id): void
    {
        $this->cambiarEstatus($id, 'activo');
    }

    public function crearNivel(string $cursoId): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $id = $this->id($cursoId);
        $payload = $this->readJsonBody();

        if ($id === null || $payload === null) {
            $this->error(400, 'Petición no válida');
            return;
        }

        $this->responder($this->courses->crearNivel(
            $id,
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        ));
    }

    public function actualizarNivel(string $id): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $nivelId = $this->id($id);
        $payload = $this->readJsonBody();

        if ($nivelId === null || $payload === null) {
            $this->error(400, 'Petición no válida');
            return;
        }

        $this->responder($this->courses->actualizarNivel(
            $nivelId,
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        ));
    }

    public function eliminarNivel(string $id): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $nivelId = $this->id($id);

        if ($nivelId === null) {
            $this->error(400, 'El identificador del nivel no es válido');
            return;
        }

        $this->responder($this->courses->eliminarNivel(
            $nivelId,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        ));
    }

    private function cambiarEstatus(string $id, string $estatus): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $cursoId = $this->id($id);

        if ($cursoId === null) {
            $this->error(400, 'El identificador del curso no es válido');
            return;
        }

        $this->responder($this->courses->cambiarEstatus(
            $cursoId,
            $estatus,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        ));
    }

    private function id(string $valor): ?int
    {
        $valor = trim($valor);

        return ctype_digit($valor) && (int) $valor > 0 ? (int) $valor : null;
    }

    private function responder(array $resultado): void
    {
        $status = (int) ($resultado['status'] ?? 200);

        if (($resultado['ok'] ?? false) !== true) {
            $this->error($status, (string) ($resultado['message'] ?? 'No se pudo completar la solicitud'), [
                'detalles' => $resultado['detalles'] ?? [],
            ]);
            return;
        }

        unset($resultado['ok'], $resultado['status'], $resultado['detalles']);
        $this->respond($resultado, $status);
    }
}
