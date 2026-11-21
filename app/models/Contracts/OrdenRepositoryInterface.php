<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface OrdenRepositoryInterface
{
    public function crear(array $datos): int;

    public function agregarDetalle(int $ordenId, ?int $nivelId, string $concepto, float $monto): void;

    public function buscarPorId(int $id): ?array;

    public function listarDetalles(int $ordenId): array;

    public function marcarPagada(int $id, string $referencia): bool;
}
