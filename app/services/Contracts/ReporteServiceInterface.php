<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ReporteServiceInterface
{
    public function generar(array $filtrosCrudos, int $usuarioId, string $rol): array;
}
