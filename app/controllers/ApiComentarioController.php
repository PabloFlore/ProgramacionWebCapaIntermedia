<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\Contracts\ComentarioServiceInterface;

final class ApiComentarioController extends JsonController
{
    public function __construct(private readonly ComentarioServiceInterface $comentarios)
    {
    }

    public function listar(string $id): void
    {
        $cursoId = $this->id($id);

        if ($cursoId === null) {
            $this->error(400, 'El identificador del curso no es válido');
            return;
        }

        $this->responder($this->comentarios->listarPorCurso($cursoId));
    }

    public function crear(string $id): void
    {
        $usuario = $this->requireRole(['estudiante']);

        if ($usuario === null) {
            return;
        }

        $cursoId = $this->id($id);

        if ($cursoId === null) {
            $this->error(400, 'El identificador del curso no es válido');
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $this->responder($this->comentarios->crear(
            $cursoId,
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId())
        ));
    }

    public function listarModeracion(): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $this->responder($this->comentarios->listarModeracion($_GET));
    }

    public function eliminar(string $id): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $comentarioId = $this->id($id);

        if ($comentarioId === null) {
            $this->error(400, 'El identificador del comentario no es válido');
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->comentarios->eliminar(
            $comentarioId,
            $payload,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId())
        );

        if (($resultado['ok'] ?? false) === true) {
            $this->flash('success', 'Comentario retirado por moderación.');
        }

        $this->responder($resultado);
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
