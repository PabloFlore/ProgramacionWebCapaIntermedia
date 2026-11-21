<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface PagoRepositoryInterface
{
    public function crear(array $datos): int;

    public function buscarPorId(int $id): ?array;

    public function buscarPorOrden(int $ordenId): ?array;

    public function aprobar(int $id, string $referencia): bool;
}
