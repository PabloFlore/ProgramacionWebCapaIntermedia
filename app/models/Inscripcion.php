<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\InscripcionRepositoryInterface;

class Inscripcion extends Model implements InscripcionRepositoryInterface
{
    public function buscar(int $estudianteId, int $cursoId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM inscripciones
             WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id
             LIMIT 1',
            ['estudiante_id' => $estudianteId, 'curso_id' => $cursoId]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, c.titulo AS curso_titulo, c.descripcion AS curso_descripcion,
                    c.instructor_id, c.categoria_id, u.nombre AS estudiante_nombre,
                    u.correo AS estudiante_correo, cat.nombre AS categoria_nombre
             FROM inscripciones i
             INNER JOIN cursos c ON c.curso_id = i.curso_id
             INNER JOIN usuarios u ON u.usuario_id = i.estudiante_id
             INNER JOIN categorias cat ON cat.categoria_id = c.categoria_id
             WHERE i.inscripcion_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function listarPorEstudiante(int $estudianteId): array
    {
        $rows = $this->fetchAll(
            'SELECT i.inscripcion_id, i.curso_id, i.progreso, i.completado, i.estado_pago,
                    i.monto_pagado, i.referencia_pago, i.fecha_inscripcion, i.fecha_completado,
                    i.orden_id, p.pago_id,
                    c.titulo, c.descripcion, c.imagen, cat.nombre AS categoria_nombre
             FROM inscripciones i
             INNER JOIN cursos c ON c.curso_id = i.curso_id
             INNER JOIN categorias cat ON cat.categoria_id = c.categoria_id
             LEFT JOIN pagos p ON p.orden_id = i.orden_id
             WHERE i.estudiante_id = :estudiante_id
             ORDER BY i.fecha_inscripcion DESC',
            ['estudiante_id' => $estudianteId]
        );

        return array_map(static function (array $fila): array {
            $fila['inscripcion_id'] = (int) $fila['inscripcion_id'];
            $fila['curso_id'] = (int) $fila['curso_id'];
            $fila['progreso'] = (int) $fila['progreso'];
            $fila['completado'] = (bool) $fila['completado'];
            $fila['monto_pagado'] = (float) $fila['monto_pagado'];
            $fila['orden_id'] = $fila['orden_id'] === null ? null : (int) $fila['orden_id'];
            $fila['pago_id'] = $fila['pago_id'] === null ? null : (int) $fila['pago_id'];

            return $fila;
        }, $rows);
    }

public function listarKardex(int $estudianteId, array $filtros = []): array
    {
        $sql = 'SELECT i.inscripcion_id, i.curso_id, i.progreso, i.completado, i.estado_pago,
                       i.monto_pagado, i.referencia_pago, i.fecha_inscripcion, i.fecha_completado,
                       c.titulo AS curso_titulo, c.estatus AS curso_estatus,
                       cat.categoria_id, cat.nombre AS categoria_nombre,
                       COUNT(n.nivel_id) AS total_niveles,
                       COALESCE(SUM(CASE WHEN pn.completado = 1 THEN 1 ELSE 0 END), 0) AS niveles_completados
                FROM inscripciones i
                INNER JOIN cursos c ON c.curso_id = i.curso_id
                LEFT JOIN categorias cat ON cat.categoria_id = c.categoria_id
                LEFT JOIN niveles n ON n.curso_id = c.curso_id
                LEFT JOIN progreso_niveles pn
                       ON pn.nivel_id = n.nivel_id AND pn.inscripcion_id = i.inscripcion_id
                WHERE i.estudiante_id = :estudiante_id';

        $params = ['estudiante_id' => $estudianteId];

        if (!empty($filtros['desde'])) {
            $sql .= ' AND DATE(i.fecha_inscripcion) >= :desde';
            $params['desde'] = $filtros['desde'];
        }

        if (!empty($filtros['hasta'])) {
            $sql .= ' AND DATE(i.fecha_inscripcion) <= :hasta';
            $params['hasta'] = $filtros['hasta'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params['categoria_id'] = (int) $filtros['categoria_id'];
        }

        if (($filtros['terminados'] ?? '') === '1') {
            $sql .= ' AND i.completado = 1';
        }

        if (($filtros['activos'] ?? '') === '1') {
            $sql .= " AND c.estatus = 'activo'";
        }

        $sql .= ' GROUP BY i.inscripcion_id, i.curso_id, i.progreso, i.completado, i.estado_pago,
                           i.monto_pagado, i.referencia_pago, i.fecha_inscripcion, i.fecha_completado,
                           c.titulo, c.estatus, cat.categoria_id, cat.nombre
                  ORDER BY i.fecha_inscripcion DESC';

        $rows = $this->fetchAll($sql, $params);

        return array_map(static function (array $fila): array {
            $fila['inscripcion_id'] = (int) $fila['inscripcion_id'];
            $fila['curso_id'] = (int) $fila['curso_id'];
            $fila['progreso'] = (int) $fila['progreso'];
            $fila['completado'] = (bool) $fila['completado'];
            $fila['monto_pagado'] = (float) $fila['monto_pagado'];
            $fila['total_niveles'] = (int) $fila['total_niveles'];
            $fila['niveles_completados'] = (int) $fila['niveles_completados'];

            return $fila;
        }, $rows);
    }

    public function crear(array $datos): int
    {
        $this->db->prepare(
            'INSERT INTO inscripciones
                (estudiante_id, curso_id, monto_pagado, estado_pago, referencia_pago,
                 metodo_pago_id, orden_id, progreso, completado)
             VALUES
                (:estudiante_id, :curso_id, :monto_pagado, :estado_pago, :referencia_pago,
                 :metodo_pago_id, :orden_id, 0, FALSE)'
        )->execute([
            'estudiante_id' => (int) $datos['estudiante_id'],
            'curso_id' => (int) $datos['curso_id'],
            'monto_pagado' => (float) $datos['monto_pagado'],
            'estado_pago' => (string) $datos['estado_pago'],
            'referencia_pago' => (string) $datos['referencia_pago'],
            'metodo_pago_id' => (int) $datos['metodo_pago_id'],
            'orden_id' => (int) $datos['orden_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function marcarPagada(int $id, float $monto, string $referencia, int $metodoPagoId, int $ordenId): bool
    {
        return $this->db->prepare(
            'UPDATE inscripciones
             SET monto_pagado = :monto, estado_pago = :estado, referencia_pago = :referencia,
                 metodo_pago_id = :metodo, orden_id = :orden, fecha_pago = NOW()
             WHERE inscripcion_id = :id'
        )->execute([
            'id' => $id,
            'monto' => $monto,
            'estado' => 'pagado',
            'referencia' => $referencia,
            'metodo' => $metodoPagoId,
            'orden' => $ordenId,
        ]);
    }

    public function actualizarProgreso(int $id, int $progreso, bool $completado): bool
    {
        return $this->db->prepare(
            'UPDATE inscripciones
             SET progreso = :progreso,
                 completado = :completado,
                  fecha_completado = IF(:completado_fecha = 1, COALESCE(fecha_completado, NOW()), NULL)
              WHERE inscripcion_id = :id'
        )->execute([
            'id' => $id,
            'progreso' => $progreso,
            'completado' => $completado ? 1 : 0,
            'completado_fecha' => $completado ? 1 : 0,
        ]);
    }
}
