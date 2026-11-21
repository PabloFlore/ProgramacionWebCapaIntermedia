<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface InscripcionRepositoryInterface
{
    public function buscar(int $estudianteId, int $cursoId): ?array;

    public function buscarPorId(int $id): ?array;

    public function listarPorEstudiante(int $estudianteId): array;

    public function listarKardex(int $estudianteId): array;

    public function crear(array $datos): int;

    public function marcarPagada(int $id, float $monto, string $referencia, int $metodoPagoId, int $ordenId): bool;

    public function actualizarProgreso(int $id, int $progreso, bool $completado): bool;
}
