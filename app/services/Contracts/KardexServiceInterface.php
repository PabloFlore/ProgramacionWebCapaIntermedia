<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface KardexServiceInterface
{
    public function obtener(int $estudianteId, array $filtros = []): array;
}
