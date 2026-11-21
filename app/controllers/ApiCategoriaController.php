<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\Contracts\CategoriaServiceInterface;

final class ApiCategoriaController extends JsonController
{
    public function __construct(private readonly CategoriaServiceInterface $categorias)
    {
    }

    public function gestion(): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $this->respond($this->categorias->listar());
    }

    public function crear(): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->categorias->crear($payload, (int) ($usuario['usuario_id'] ?? Session::usuarioId()));

        if (($resultado['ok'] ?? false) === true) {
            $this->flash('success', 'Categoría creada correctamente.');
        }

        $this->responder($resultado);
    }

    public function actualizar(string $id): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $categoriaId = $this->id($id);

        if ($categoriaId === null) {
            $this->error(400, 'El identificador de la categoría no es válido');
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->categorias->actualizar($categoriaId, $payload);

        if (($resultado['ok'] ?? false) === true) {
            $this->flash('success', 'Categoría actualizada correctamente.');
        }

        $this->responder($resultado);
    }

    public function eliminar(string $id): void
    {
        $usuario = $this->requireRole(['admin']);

        if ($usuario === null) {
            return;
        }

        $categoriaId = $this->id($id);

        if ($categoriaId === null) {
            $this->error(400, 'El identificador de la categoría no es válido');
            return;
        }

        $resultado = $this->categorias->eliminar($categoriaId);

        if (($resultado['ok'] ?? false) === true) {
            $this->flash('success', 'Categoría eliminada correctamente.');
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
