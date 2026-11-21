<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface ProgresoNivelRepositoryInterface
{
    public function asegurar(int $inscripcionId, int $nivelId): bool;

    public function marcarCompletado(int $inscripcionId, int $nivelId): bool;

    public function contarCompletados(int $inscripcionId): int;

    public function listarPorInscripcion(int $inscripcionId): array;
}
