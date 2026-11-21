<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface NivelRepositoryInterface
{
    public function listarPorCurso(int $cursoId): array;

    public function buscarPorId(int $id): ?array;

    public function crear(array $datos): int;

    public function actualizar(int $id, array $datos): bool;

    public function eliminar(int $id): bool;

    public function tieneProgreso(int $id): bool;
}
