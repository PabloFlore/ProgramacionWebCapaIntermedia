<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\CursoRepositoryInterface;

class Curso extends Model implements CursoRepositoryInterface
{
    public function listarActivosPorCategoria(
        ?int $categoriaId,
        ?string $buscar = null,
        int $limit = 10,
        int $offset = 0
    ): array {
        $sql = 'SELECT c.curso_id, c.titulo, c.descripcion, c.imagen, c.precio, c.tipo_precio,
                        c.instructor_id, c.categoria_id, c.estatus, c.fecha_creacion,
                        (SELECT ROUND(AVG(com.calificacion), 2)
                         FROM comentarios com
                         WHERE com.curso_id = c.curso_id AND com.eliminado_por_admin = 0) AS calificacion_promedio,
                        (SELECT COUNT(*)
                         FROM comentarios com
                         WHERE com.curso_id = c.curso_id AND com.eliminado_por_admin = 0) AS total_comentarios
                 FROM cursos c
                 LEFT JOIN categorias cat ON cat.categoria_id = c.categoria_id
                 WHERE c.estatus = :estatus';
        $params = [':estatus' => ['activo', \PDO::PARAM_STR]];

        if ($categoriaId !== null) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params[':categoria_id'] = [$categoriaId, \PDO::PARAM_INT];
        }

        if ($buscar !== null && $buscar !== '') {
            $sql .= ' AND (c.titulo LIKE :buscar_titulo OR c.descripcion LIKE :buscar_descripcion OR cat.nombre LIKE :buscar_categoria)';
            $params[':buscar_titulo'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
            $params[':buscar_descripcion'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
            $params[':buscar_categoria'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
        }

        $sql .= ' ORDER BY c.curso_id DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = [$limit, \PDO::PARAM_INT];
        $params[':offset'] = [$offset, \PDO::PARAM_INT];

        $statement = $this->db->prepare($sql);

        foreach ($params as $marcador => [$valor, $tipo]) {
            $statement->bindValue($marcador, $valor, $tipo);
        }

        $statement->execute();

        return array_map(static function (array $curso): array {
            $curso['curso_id'] = (int) $curso['curso_id'];
            $curso['precio'] = (float) $curso['precio'];
            $curso['instructor_id'] = (int) $curso['instructor_id'];
            $curso['categoria_id'] = (int) $curso['categoria_id'];
            $curso['calificacion_promedio'] = $curso['calificacion_promedio'] === null
                ? null
                : (float) $curso['calificacion_promedio'];
            $curso['total_comentarios'] = (int) $curso['total_comentarios'];

            return $curso;
        }, $statement->fetchAll());
    }

    public function listarActivos(): array
    {
        $rows = $this->fetchAll(
            'SELECT curso_id, titulo
             FROM cursos
             WHERE estatus = :estatus
             ORDER BY titulo ASC',
            ['estatus' => 'activo']
        );

        return array_map(static function (array $curso): array {
            $curso['curso_id'] = (int) $curso['curso_id'];

            return $curso;
        }, $rows);
    }

    public function contarActivosPorCategoria(?int $categoriaId, ?string $buscar = null): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM cursos c
                LEFT JOIN categorias cat ON cat.categoria_id = c.categoria_id
                WHERE c.estatus = :estatus';
        $params = [':estatus' => ['activo', \PDO::PARAM_STR]];

        if ($categoriaId !== null) {
            $sql .= ' AND c.categoria_id = :categoria_id';
            $params[':categoria_id'] = [$categoriaId, \PDO::PARAM_INT];
        }

        if ($buscar !== null && $buscar !== '') {
            $sql .= ' AND (c.titulo LIKE :buscar_titulo OR c.descripcion LIKE :buscar_descripcion OR cat.nombre LIKE :buscar_categoria)';
            $params[':buscar_titulo'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
            $params[':buscar_descripcion'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
            $params[':buscar_categoria'] = ['%' . $buscar . '%', \PDO::PARAM_STR];
        }

        $statement = $this->db->prepare($sql);

        foreach ($params as $marcador => [$valor, $tipo]) {
            $statement->bindValue($marcador, $valor, $tipo);
        }

        $statement->execute();
        $row = $statement->fetch();

        return $row ? (int) $row['total'] : 0;
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO cursos (titulo, descripcion, imagen, precio, tipo_precio, instructor_id, categoria_id, estatus)
                VALUES (:titulo, :descripcion, :imagen, :precio, :tipo_precio, :instructor_id, :categoria_id, :estatus)';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':titulo', $datos['titulo'] ?? '', \PDO::PARAM_STR);
        $statement->bindValue(':descripcion', $datos['descripcion'] ?? null, \PDO::PARAM_STR);
        $statement->bindValue(':imagen', $datos['imagen'] ?? null, \PDO::PARAM_STR);
        $statement->bindValue(':precio', isset($datos['precio']) ? (float) $datos['precio'] : 0.00);
        $statement->bindValue(':tipo_precio', $datos['tipo_precio'] ?? 'curso', \PDO::PARAM_STR);
        $statement->bindValue(':instructor_id', (int) ($datos['instructor_id'] ?? 0), \PDO::PARAM_INT);
        $statement->bindValue(':categoria_id', (int) ($datos['categoria_id'] ?? 0), \PDO::PARAM_INT);
        $statement->bindValue(':estatus', $datos['estatus'] ?? 'activo', \PDO::PARAM_STR);
        $statement->execute();

        return (int) $this->db->lastInsertId();
    }

    public function buscarPorId(int $id, bool $incluirInactivos = true): ?array
    {
        $sql = 'SELECT c.curso_id, c.titulo, c.descripcion, c.imagen, c.precio, c.tipo_precio,
                       c.instructor_id, c.categoria_id, c.estatus, c.fecha_creacion,
                       cat.nombre AS categoria_nombre, u.nombre AS instructor_nombre,
                       (SELECT ROUND(AVG(com.calificacion), 2)
                        FROM comentarios com
                        WHERE com.curso_id = c.curso_id AND com.eliminado_por_admin = 0) AS calificacion_promedio,
                       (SELECT COUNT(*)
                        FROM comentarios com
                        WHERE com.curso_id = c.curso_id AND com.eliminado_por_admin = 0) AS total_comentarios
                FROM cursos c
                INNER JOIN categorias cat ON cat.categoria_id = c.categoria_id
                INNER JOIN usuarios u ON u.usuario_id = c.instructor_id
                WHERE c.curso_id = :id';

        if (!$incluirInactivos) {
            $sql .= " AND c.estatus = 'activo'";
        }

        $sql .= ' LIMIT 1';

        $curso = $this->fetchOne($sql, ['id' => $id]);

        if ($curso === null) {
            return null;
        }

        $curso['curso_id'] = (int) $curso['curso_id'];
        $curso['precio'] = (float) $curso['precio'];
        $curso['instructor_id'] = (int) $curso['instructor_id'];
        $curso['categoria_id'] = (int) $curso['categoria_id'];
        $curso['calificacion_promedio'] = $curso['calificacion_promedio'] === null
            ? null
            : (float) $curso['calificacion_promedio'];
        $curso['total_comentarios'] = (int) $curso['total_comentarios'];

        return $curso;
    }

    public function listar(?string $estatus = null, ?int $instructorId = null, int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT c.curso_id, c.titulo, c.precio, c.estatus, c.fecha_creacion,
                       cat.nombre AS categoria_nombre, u.nombre AS instructor_nombre
                FROM cursos c
                INNER JOIN categorias cat ON cat.categoria_id = c.categoria_id
                INNER JOIN usuarios u ON u.usuario_id = c.instructor_id
                WHERE 1 = 1';
        $params = [];

        if ($estatus !== null) {
            $sql .= ' AND c.estatus = :estatus';
            $params['estatus'] = $estatus;
        }

        if ($instructorId !== null) {
            $sql .= ' AND c.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }

        $sql .= ' ORDER BY c.curso_id DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);

        foreach ($params as $clave => $valor) {
            $statement->bindValue(':' . $clave, $valor);
        }

        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        $rows = $statement->fetchAll();

        return array_map(static function (array $curso): array {
            $curso['curso_id'] = (int) $curso['curso_id'];
            $curso['precio'] = (float) $curso['precio'];

            return $curso;
        }, $rows);
    }

    public function contar(?string $estatus = null, ?int $instructorId = null): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM cursos c WHERE 1 = 1';
        $params = [];

        if ($estatus !== null) {
            $sql .= ' AND c.estatus = :estatus';
            $params['estatus'] = $estatus;
        }

        if ($instructorId !== null) {
            $sql .= ' AND c.instructor_id = :instructor_id';
            $params['instructor_id'] = $instructorId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $fila = $statement->fetch();

        return $fila ? (int) $fila['total'] : 0;
    }

    public function actualizar(int $id, array $datos): bool
    {
        return $this->db->prepare(
            'UPDATE cursos
             SET titulo = :titulo, descripcion = :descripcion, imagen = :imagen, precio = :precio,
                 tipo_precio = :tipo_precio, instructor_id = :instructor_id, categoria_id = :categoria_id
             WHERE curso_id = :id'
        )->execute([
            'id' => $id,
            'titulo' => (string) $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'imagen' => $datos['imagen'],
            'precio' => (float) $datos['precio'],
            'tipo_precio' => (string) $datos['tipo_precio'],
            'instructor_id' => (int) $datos['instructor_id'],
            'categoria_id' => (int) $datos['categoria_id'],
        ]);
    }

    public function cambiarEstatus(int $id, string $estatus): bool
    {
        return $this->db->prepare('UPDATE cursos SET estatus = :estatus WHERE curso_id = :id')
            ->execute([
                'id' => $id,
                'estatus' => $estatus,
            ]);
    }
}
