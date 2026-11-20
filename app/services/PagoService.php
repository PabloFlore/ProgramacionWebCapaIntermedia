<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Orden;
use App\Models\Pago;

final class PagoService
{
    public function __construct(
        private readonly Pago $pagos,
        private readonly Orden $ordenes
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
