<?php

declare(strict_types=1);

namespace App\Models;

class Orden extends Model
{
    public function crear(array $datos): int
    {
        $this->db->prepare(
            'INSERT INTO ordenes (estudiante_id, curso_id, monto, referencia)
             VALUES (:estudiante_id, :curso_id, :monto, :referencia)'
        )->execute([
            'estudiante_id' => (int) $datos['estudiante_id'],
            'curso_id' => (int) $datos['curso_id'],
            'monto' => (float) $datos['monto'],
            'referencia' => (string) $datos['referencia'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function agregarDetalle(int $ordenId, ?int $nivelId, string $concepto, float $monto): void
    {
        $this->db->prepare(
            'INSERT INTO orden_detalles (orden_id, nivel_id, concepto, monto, cantidad)
             VALUES (:orden_id, :nivel_id, :concepto, :monto, 1)'
        )->execute([
            'orden_id' => $ordenId,
            'nivel_id' => $nivelId,
            'concepto' => $concepto,
            'monto' => $monto,
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT o.*, c.titulo AS curso_titulo, u.nombre AS estudiante_nombre
             FROM ordenes o
             INNER JOIN cursos c ON c.curso_id = o.curso_id
             INNER JOIN usuarios u ON u.usuario_id = o.estudiante_id
             WHERE o.orden_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function listarDetalles(int $ordenId): array
    {
        return $this->fetchAll(
            'SELECT detalle_id, nivel_id, concepto, monto FROM orden_detalles WHERE orden_id = :id ORDER BY detalle_id ASC',
            ['id' => $ordenId]
        );
    }

    public function marcarPagada(int $id, string $referencia): bool
    {
        return $this->db->prepare(
            "UPDATE ordenes SET estatus = 'pagada', referencia = :referencia, fecha_pago = NOW() WHERE orden_id = :id"
        )->execute([
            'id' => $id,
            'referencia' => $referencia,
        ]);
    }
}
