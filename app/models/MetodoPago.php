<?php

declare(strict_types=1);

namespace App\Models;

class MetodoPago extends Model
{
    public function listarActivos(): array
    {
        $rows = $this->fetchAll(
            'SELECT metodo_pago_id, codigo, nombre
             FROM metodos_pago
             WHERE activo = TRUE
             ORDER BY metodo_pago_id ASC'
        );

        return array_map(static function (array $metodo): array {
            $metodo['metodo_pago_id'] = (int) $metodo['metodo_pago_id'];

            return $metodo;
        }, $rows);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT metodo_pago_id, codigo, nombre
             FROM metodos_pago
             WHERE metodo_pago_id = :id AND activo = TRUE
             LIMIT 1',
            ['id' => $id]
        );
    }
}
