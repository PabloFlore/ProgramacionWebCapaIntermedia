<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\CategoriaRepositoryInterface;

class Categoria extends Model implements CategoriaRepositoryInterface
{
    public function listar(): array
    {
        $rows = $this->fetchAll(
            'SELECT categoria_id, nombre, descripcion
             FROM categorias
             ORDER BY nombre ASC'
        );

        return array_map(static function (array $categoria): array {
            $categoria['categoria_id'] = (int) $categoria['categoria_id'];

            return $categoria;
        }, $rows);
    }

    public function existe(int $categoriaId): bool
    {
        $row = $this->fetchOne(
            'SELECT categoria_id FROM categorias WHERE categoria_id = :id LIMIT 1',
            ['id' => $categoriaId]
        );

        return $row !== null;
    }

    public function listarGestion(): array
    {
        $filas = $this->fetchAll(
            'SELECT c.categoria_id, c.nombre, c.descripcion, c.creado_por,
                    u.nombre AS creado_por_nombre,
                    (SELECT COUNT(*) FROM cursos cur WHERE cur.categoria_id = c.categoria_id) AS total_cursos
             FROM categorias c
             LEFT JOIN usuarios u ON u.usuario_id = c.creado_por
             ORDER BY c.nombre ASC'
        );

        return array_map(static function (array $categoria): array {
            $categoria['categoria_id'] = (int) $categoria['categoria_id'];
            $categoria['creado_por'] = $categoria['creado_por'] === null ? null : (int) $categoria['creado_por'];
            $categoria['total_cursos'] = (int) $categoria['total_cursos'];

            return $categoria;
        }, $filas);
    }

    public function buscarPorId(int $categoriaId): ?array
    {
        $categoria = $this->fetchOne(
            'SELECT categoria_id, nombre, descripcion, creado_por
             FROM categorias WHERE categoria_id = :id LIMIT 1',
            ['id' => $categoriaId]
        );

        if ($categoria === null) {
            return null;
        }

        $categoria['categoria_id'] = (int) $categoria['categoria_id'];
        $categoria['creado_por'] = $categoria['creado_por'] === null ? null : (int) $categoria['creado_por'];

        return $categoria;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $sql = 'SELECT categoria_id FROM categorias WHERE nombre = :nombre';
        $params = ['nombre' => $nombre];

        if ($excluirId !== null) {
            $sql .= ' AND categoria_id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        return $this->fetchOne($sql . ' LIMIT 1', $params) !== null;
    }

    public function crear(string $nombre, ?string $descripcion, int $creadoPor): int
    {
        $this->execute(
            'INSERT INTO categorias (nombre, descripcion, creado_por) VALUES (:nombre, :descripcion, :creado_por)',
            [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'creado_por' => $creadoPor,
            ]
        );

        return $this->lastInsertId();
    }

    public function actualizar(int $categoriaId, string $nombre, ?string $descripcion): bool
    {
        return $this->execute(
            'UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE categoria_id = :id',
            [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'id' => $categoriaId,
            ]
        );
    }

    public function tieneCursos(int $categoriaId): bool
    {
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM cursos WHERE categoria_id = :id',
            ['id' => $categoriaId]
        );

        return $fila !== null && (int) $fila['total'] > 0;
    }

    public function eliminar(int $categoriaId): bool
    {
        return $this->execute(
            'DELETE FROM categorias WHERE categoria_id = :id',
            ['id' => $categoriaId]
        );
    }
}
