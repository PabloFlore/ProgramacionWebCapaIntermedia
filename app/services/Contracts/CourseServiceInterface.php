<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface CourseServiceInterface
{
    public function detalle(int $cursoId): ?array;

    public function detallePublico(int $cursoId): ?array;

    public function detalleGestion(int $cursoId, int $usuarioId, string $rol): array;

    public function listarGestion(?int $instructorId, int $pagina, int $limite): array;

    public function crear(array $datos, int $usuarioId, string $rol): array;

    public function actualizar(int $cursoId, array $datos, int $usuarioId, string $rol): array;

    public function cambiarEstatus(int $cursoId, string $estatus, int $usuarioId, string $rol): array;

    public function crearNivel(int $cursoId, array $datos, int $usuarioId, string $rol): array;

    public function actualizarNivel(int $nivelId, array $datos, int $usuarioId, string $rol): array;

    public function eliminarNivel(int $nivelId, int $usuarioId, string $rol): array;
}
