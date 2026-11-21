<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface InscripcionServiceInterface
{
    public function metodosPago(): array;

    public function misInscripciones(int $estudianteId): array;

    public function comprar(int $estudianteId, int $cursoId, int $metodoPagoId, ?int $nivelId = null): array;

    public function confirmarPago(int $pagoId, int $estudianteId): array;

    public function completarNivel(int $estudianteId, int $inscripcionId, int $nivelId): array;

    public function verificarDiploma(string $codigo): ?array;

    public function diplomaPorInscripcion(int $estudianteId, int $inscripcionId): ?array;
}
