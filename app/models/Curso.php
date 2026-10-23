<?php

declare(strict_types=1);

namespace App\Models;

class Curso extends Model
{
    public function listarActivosPorCategoria(int $categoriaId, int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT curso_id, titulo, descripcion, imagen, precio, tipo_precio, 
                       instructor_id, categoria_id, estatus, fecha_creacion
                FROM cursos
                WHERE estatus = :estatus
                  AND categoria_id = :categoria_id
                ORDER BY curso_id DESC
                LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':estatus', 'activo', \PDO::PARAM_STR);
        $statement->bindValue(':categoria_id', $categoriaId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function contarActivosPorCategoria(int $categoriaId): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM cursos
                WHERE estatus = :estatus
                  AND categoria_id = :categoria_id';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':estatus', 'activo', \PDO::PARAM_STR);
        $statement->bindValue(':categoria_id', $categoriaId, \PDO::PARAM_INT);
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
}
