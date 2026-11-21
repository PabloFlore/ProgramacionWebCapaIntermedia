<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\PagoRepositoryInterface;

class Pago extends Model implements PagoRepositoryInterface
{
    public function crear(array $datos): int
    {
        $this->db->prepare(
            'INSERT INTO pagos (orden_id, metodo_pago_id, monto, referencia)
             VALUES (:orden_id, :metodo_pago_id, :monto, :referencia)'
        )->execute([
            'orden_id' => (int) $datos['orden_id'],
            'metodo_pago_id' => (int) $datos['metodo_pago_id'],
            'monto' => (float) $datos['monto'],
            'referencia' => (string) $datos['referencia'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, o.estudiante_id, o.curso_id, o.estatus AS orden_estatus,
                    m.codigo AS metodo_codigo, m.nombre AS metodo_nombre
             FROM pagos p
             INNER JOIN ordenes o ON o.orden_id = p.orden_id
             INNER JOIN metodos_pago m ON m.metodo_pago_id = p.metodo_pago_id
             WHERE p.pago_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function buscarPorOrden(int $ordenId): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, m.codigo AS metodo_codigo, m.nombre AS metodo_nombre
             FROM pagos p
             INNER JOIN metodos_pago m ON m.metodo_pago_id = p.metodo_pago_id
             WHERE p.orden_id = :orden_id
             ORDER BY p.pago_id DESC
             LIMIT 1',
            ['orden_id' => $ordenId]
        );
    }

    public function aprobar(int $id, string $referencia): bool
    {
        return $this->db->prepare(
            "UPDATE pagos SET estatus = 'aprobado', referencia = :referencia, fecha_confirmacion = NOW() WHERE pago_id = :id"
        )->execute([
            'id' => $id,
            'referencia' => $referencia,
        ]);
    }
}
