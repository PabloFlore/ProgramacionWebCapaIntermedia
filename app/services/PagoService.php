<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\OrdenRepositoryInterface;
use App\Models\Contracts\PagoRepositoryInterface;
use App\Services\Contracts\PagoServiceInterface;

final class PagoService implements PagoServiceInterface
{
    public function __construct(
        private readonly PagoRepositoryInterface $pagos,
        private readonly OrdenRepositoryInterface $ordenes
    ) {
    }

    public function buscar(int $pagoId): ?array
    {
        return $this->pagos->buscarPorId($pagoId);
    }

    public function aprobar(array $pago, string $referencia): void
    {
        $this->pagos->aprobar((int) $pago['pago_id'], $referencia);
        $this->ordenes->marcarPagada((int) $pago['orden_id'], $referencia);
    }
}
