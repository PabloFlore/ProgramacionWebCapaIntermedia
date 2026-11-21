<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface DiplomaServiceInterface
{
    public function emitir(int $inscripcionId): array;

    public function porInscripcion(int $inscripcionId): ?array;

    public function verificar(string $codigo): ?array;
}
