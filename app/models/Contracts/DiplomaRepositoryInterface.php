<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface DiplomaRepositoryInterface
{
    public function buscarPorInscripcion(int $inscripcionId): ?array;

    public function buscarPorCodigo(string $codigo): ?array;

    public function crear(int $inscripcionId, string $codigo): int;
}
