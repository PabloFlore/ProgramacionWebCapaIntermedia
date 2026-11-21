<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\CategoriaRepositoryInterface;
use App\Services\Contracts\CategoriaServiceInterface;

class CategoriaService implements CategoriaServiceInterface
{
    public function __construct(private CategoriaRepositoryInterface $categorias)
    {
    }

    public function listar(): array
    {
        return [
            'ok' => true,
            'categorias' => $this->categorias->listarGestion(),
        ];
    }

    public function crear(array $datos, int $adminId): array
    {
        $validacion = $this->validar($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, 'No se pudo crear la categoría.', $validacion['errores']);
        }

        if ($this->categorias->existeNombre($validacion['nombre'])) {
            return $this->fallo(409, 'Ya existe una categoría con ese nombre.', ['El nombre debe ser único.']);
        }

        $categoriaId = $this->categorias->crear($validacion['nombre'], $validacion['descripcion'], $adminId);

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Categoría creada correctamente.',
            'categoria' => $this->categorias->buscarPorId($categoriaId),
        ];
    }

    public function actualizar(int $categoriaId, array $datos): array
    {
        if (!$this->categorias->existe($categoriaId)) {
            return $this->fallo(404, 'Categoría no encontrada.');
        }

        $validacion = $this->validar($datos);

        if ($validacion['errores'] !== []) {
            return $this->fallo(400, 'No se pudo actualizar la categoría.', $validacion['errores']);
        }

        if ($this->categorias->existeNombre($validacion['nombre'], $categoriaId)) {
            return $this->fallo(409, 'Ya existe una categoría con ese nombre.', ['El nombre debe ser único.']);
        }

        $this->categorias->actualizar($categoriaId, $validacion['nombre'], $validacion['descripcion']);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Categoría actualizada correctamente.',
            'categoria' => $this->categorias->buscarPorId($categoriaId),
        ];
    }

    public function eliminar(int $categoriaId): array
    {
        if (!$this->categorias->existe($categoriaId)) {
            return $this->fallo(404, 'Categoría no encontrada.');
        }

        if ($this->categorias->tieneCursos($categoriaId)) {
            return $this->fallo(409, 'No se puede eliminar la categoría.', [
                'Tiene cursos asociados; reasígnalos antes de eliminarla.',
            ]);
        }

        $this->categorias->eliminar($categoriaId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Categoría eliminada correctamente.',
        ];
    }

    private function validar(array $datos): array
    {
        $errores = [];
        $nombre = isset($datos['nombre']) && is_string($datos['nombre']) ? trim($datos['nombre']) : '';
        $descripcion = isset($datos['descripcion']) && is_string($datos['descripcion'])
            ? trim($datos['descripcion'])
            : '';

        if ($nombre === '' || strlen($nombre) > 100) {
            $errores[] = 'El nombre es obligatorio y no puede superar 100 caracteres.';
        }

        if (strlen($descripcion) > 255) {
            $errores[] = 'La descripción no puede superar 255 caracteres.';
        }

        return [
            'errores' => $errores,
            'nombre' => $nombre,
            'descripcion' => $descripcion === '' ? null : $descripcion,
        ];
    }

    private function fallo(int $status, string $message, array $detalles = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'detalles' => $detalles !== [] ? $detalles : [$message],
        ];
    }
}
