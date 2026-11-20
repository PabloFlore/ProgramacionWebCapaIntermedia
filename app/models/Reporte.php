<?php

declare(strict_types=1);

namespace App\Models;

class Reporte extends Model
{
    public function resumenUsuarios(array $filtros, ?int $instructorId): array
    {
        [$where, $params] = $this->whereUsuarios($filtros, $instructorId);
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN u.bloqueado = 0 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN u.bloqueado = 1 THEN 1 ELSE 0 END) AS bloqueados
             FROM usuarios u' . $where,
            $params
        );

        return [
            'total' => (int) ($fila['total'] ?? 0),
            'activos' => (int) ($fila['activos'] ?? 0),
            'bloqueados' => (int) ($fila['bloqueados'] ?? 0),
        ];
    }

    public function usuariosPorRol(array $filtros, ?int $instructorId): array
    {
        [$where, $params] = $this->whereUsuarios($filtros, $instructorId);
        $filas = $this->fetchAll(
            'SELECT u.rol,
                    SUM(CASE WHEN u.bloqueado = 0 THEN 1 ELSE 0 END) AS activos,
                    SUM(CASE WHEN u.bloqueado = 1 THEN 1 ELSE 0 END) AS bloqueados,
                    COUNT(*) AS total
             FROM usuarios u' . $where . '
             GROUP BY u.rol
             ORDER BY FIELD(u.rol, "admin", "instructor", "estudiante")',
            $params
        );

        return array_map(static function (array $fila): array {
            $fila['activos'] = (int) $fila['activos'];
            $fila['bloqueados'] = (int) $fila['bloqueados'];
            $fila['total'] = (int) $fila['total'];

            return $fila;
        }, $filas);
    }

    public function contarCursos(array $filtros, ?int $instructorId): int
    {
        [$where, $params] = $this->whereCursos($filtros, $instructorId);
        $fila = $this->fetchOne('SELECT COUNT(*) AS total FROM cursos c' . $where, $params);

        return $fila ? (int) $fila['total'] : 0;
    }

    public function resumenVentas(array $filtros, ?int $instructorId): array
    {
        [$where, $params] = $this->whereVentas($filtros, $instructorId);
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS inscripciones,
                    SUM(CASE WHEN i.estado_pago = "pagado" THEN 1 ELSE 0 END) AS ventas_pagadas,
                    COALESCE(SUM(CASE WHEN i.estado_pago = "pagado" THEN i.monto_pagado ELSE 0 END), 0) AS ingresos' . $this->fromVentas() . $where,
            $params
        );

        return [
            'inscripciones' => (int) ($fila['inscripciones'] ?? 0),
            'ventas_pagadas' => (int) ($fila['ventas_pagadas'] ?? 0),
            'ingresos' => (float) ($fila['ingresos'] ?? 0),
        ];
    }

    public function ventasPorCurso(array $filtros, ?int $instructorId): array
    {
        [$where, $params] = $this->whereVentas($filtros, $instructorId);
        $filas = $this->fetchAll(
            'SELECT c.curso_id, c.titulo, cat.nombre AS categoria_nombre,
                    COUNT(*) AS inscripciones,
                    SUM(CASE WHEN i.estado_pago = "pagado" THEN 1 ELSE 0 END) AS ventas,
                    COALESCE(SUM(CASE WHEN i.estado_pago = "pagado" THEN i.monto_pagado ELSE 0 END), 0) AS monto' . $this->fromVentas() . $where . '
             GROUP BY c.curso_id, c.titulo, cat.nombre
             ORDER BY monto DESC, c.titulo ASC',
            $params
        );

        return array_map(static function (array $fila): array {
            $fila['curso_id'] = (int) $fila['curso_id'];
            $fila['inscripciones'] = (int) $fila['inscripciones'];
            $fila['ventas'] = (int) $fila['ventas'];
            $fila['monto'] = (float) $fila['monto'];

            return $fila;
        }, $filas);
    }

    public function contarDetalle(array $filtros, ?int $instructorId): int
    {
        [$where, $params] = $this->whereVentas($filtros, $instructorId);
        $fila = $this->fetchOne('SELECT COUNT(*) AS total' . $this->fromVentas() . $where, $params);

        return $fila ? (int) $fila['total'] : 0;
    }

    public function detalleVentas(array $filtros, ?int $instructorId, int $limite, int $offset): array
    {
        [$where, $params] = $this->whereVentas($filtros, $instructorId);
        $sql = 'SELECT i.inscripcion_id, c.curso_id, u.nombre AS estudiante, u.correo AS estudiante_correo,
                       c.titulo AS curso, cat.nombre AS categoria_nombre,
                       DATE(COALESCE(i.fecha_pago, i.fecha_inscripcion)) AS fecha,
                       i.monto_pagado, i.estado_pago, COALESCE(mp.nombre, "Sin pago registrado") AS metodo_pago'
            . $this->fromVentas() . $where . '
             ORDER BY fecha DESC, i.inscripcion_id DESC
             LIMIT :limite OFFSET :offset';

        $statement = $this->db->prepare($sql);

        foreach ($params as $clave => $valor) {
            $statement->bindValue(':' . $clave, $valor);
        }

        $statement->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();
        $filas = $statement->fetchAll();

        return array_map(static function (array $fila): array {
            $fila['inscripcion_id'] = (int) $fila['inscripcion_id'];
            $fila['curso_id'] = (int) $fila['curso_id'];
            $fila['monto_pagado'] = (float) $fila['monto_pagado'];

            return $fila;
        }, $filas);
    }

    private function fromVentas(): string
    {
        return ' FROM inscripciones i
             INNER JOIN cursos c ON c.curso_id = i.curso_id
             INNER JOIN categorias cat ON cat.categoria_id = c.categoria_id
             INNER JOIN usuarios u ON u.usuario_id = i.estudiante_id
             LEFT JOIN metodos_pago mp ON mp.metodo_pago_id = i.metodo_pago_id';
    }

    private function whereUsuarios(array $filtros, ?int $instructorId): array
    {
        $sql = ' WHERE 1 = 1';
        $params = [];

        if ($filtros['rol'] !== null) {
            $sql .= ' AND u.rol = :rol';
            $params['rol'] = $filtros['rol'];
        }

        if ($filtros['bloqueado'] !== null) {
            $sql .= ' AND u.bloqueado = :bloqueado';
            $params['bloqueado'] = $filtros['bloqueado'];
        }

        if ($instructorId !== null) {
            $sql .= ' AND u.usuario_id IN (
                SELECT DISTINCT i2.estudiante_id
                FROM inscripciones i2
                INNER JOIN cursos c2 ON c2.curso_id = i2.curso_id
                WHERE c2.instructor_id = :instructor_id
            )';
            $params['instructor_id'] = $instructorId;
        }

        return [$sql, $params];
    }

    private function whereCursos(array $filtros, ?int $instructorId): array
    {
        $sql = ' WHERE 1 = 1';
        $params = [];

        if ($filtros['curso_id'] !== null) {
            $sql .= ' AND c.curso_id = :curso_id';
            $params['curso_id'] = $filtros['curso_id'];
        }

        if ($filtros['categoria_id'] !== null) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        if ($instructorId !== null) {
            $sql .= ' AND c.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }

        return [$sql, $params];
    }

    private function whereVentas(array $filtros, ?int $instructorId): array
    {
        $sql = ' WHERE 1 = 1';
        $params = [];

        if ($filtros['curso_id'] !== null) {
            $sql .= ' AND i.curso_id = :curso_id';
            $params['curso_id'] = $filtros['curso_id'];
        }

        if ($filtros['categoria_id'] !== null) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros['categoria_id'];
        }

        if ($filtros['estado_pago'] !== null) {
            $sql .= ' AND i.estado_pago = :estado_pago';
            $params['estado_pago'] = $filtros['estado_pago'];
        }

        if ($filtros['desde'] !== null) {
            $sql .= ' AND DATE(COALESCE(i.fecha_pago, i.fecha_inscripcion)) >= :desde';
            $params['desde'] = $filtros['desde'];
        }

        if ($filtros['hasta'] !== null) {
            $sql .= ' AND DATE(COALESCE(i.fecha_pago, i.fecha_inscripcion)) <= :hasta';
            $params['hasta'] = $filtros['hasta'];
        }

        if ($instructorId !== null) {
            $sql .= ' AND c.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }

        return [$sql, $params];
    }
}
