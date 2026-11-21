<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface PagoServiceInterface
{
    public function buscar(int $pagoId): ?array;

    public function aprobar(array $pago, string $referencia): void;
}
