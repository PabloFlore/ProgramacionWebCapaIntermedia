<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface CategoriaServiceInterface
{
    public function listar(): array;

    public function crear(array $datos, int $adminId): array;

    public function actualizar(int $categoriaId, array $datos): array;

    public function eliminar(int $categoriaId): array;
}
