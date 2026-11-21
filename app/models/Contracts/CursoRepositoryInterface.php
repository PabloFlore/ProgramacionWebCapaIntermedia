<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface CursoRepositoryInterface
{
    public function listarActivosPorCategoria(?int $categoriaId, ?string $buscar = null, int $limite = 10, int $desplazamiento = 0): array;

    public function listarActivos(): array;

    public function contarActivosPorCategoria(?int $categoriaId, ?string $buscar = null): int;

    public function crear(array $datos): int;

    public function buscarPorId(int $id, bool $incluirInactivos = true): ?array;

    public function listar(?string $estatus = null, ?int $instructorId = null, int $limite = 20, int $desplazamiento = 0): array;

    public function contar(?string $estatus = null, ?int $instructorId = null): int;

    public function actualizar(int $id, array $datos): bool;

    public function cambiarEstatus(int $id, string $estatus): bool;
}
