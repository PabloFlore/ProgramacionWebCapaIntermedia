<?php

declare(strict_types=1);

namespace App\Models;

class Nivel extends Model
{
    public function listarPorCurso(int $cursoId): array
    {
        $rows = $this->fetchAll(
            'SELECT nivel_id, curso_id, titulo, video_url, orden, precio, material_url, material_tipo
             FROM niveles
             WHERE curso_id = :curso_id
             ORDER BY orden ASC, nivel_id ASC',
            ['curso_id' => $cursoId]
        );

        return array_map(static function (array $nivel): array {
            $nivel['nivel_id'] = (int) $nivel['nivel_id'];
            $nivel['curso_id'] = (int) $nivel['curso_id'];
            $nivel['orden'] = (int) $nivel['orden'];
            $nivel['precio'] = $nivel['precio'] === null ? null : (float) $nivel['precio'];

            return $nivel;
        }, $rows);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT nivel_id, curso_id, titulo, video_url, orden, precio, material_url, material_tipo
             FROM niveles
             WHERE nivel_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function crear(array $datos): int
    {
        $this->db->prepare(
            'INSERT INTO niveles (curso_id, titulo, video_url, orden, precio, material_url, material_tipo)
             VALUES (:curso_id, :titulo, :video_url, :orden, :precio, :material_url, :material_tipo)'
        )->execute([
            'curso_id' => (int) $datos['curso_id'],
            'titulo' => (string) $datos['titulo'],
            'video_url' => (string) $datos['video_url'],
            'orden' => (int) $datos['orden'],
            'precio' => $datos['precio'],
            'material_url' => $datos['material_url'],
            'material_tipo' => $datos['material_tipo'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $statement = $this->db->prepare(
            'UPDATE niveles
             SET titulo = :titulo, video_url = :video_url, orden = :orden, precio = :precio,
                 material_url = :material_url, material_tipo = :material_tipo
             WHERE nivel_id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'titulo' => (string) $datos['titulo'],
            'video_url' => (string) $datos['video_url'],
            'orden' => (int) $datos['orden'],
            'precio' => $datos['precio'],
            'material_url' => $datos['material_url'],
            'material_tipo' => $datos['material_tipo'],
        ]);
    }

    public function eliminar(int $id): bool
    {
        return $this->db->prepare('DELETE FROM niveles WHERE nivel_id = :id')
            ->execute(['id' => $id]);
    }

    public function tieneProgreso(int $id): bool
    {
        return $this->fetchOne(
            'SELECT progreso_id FROM progreso_niveles WHERE nivel_id = :id LIMIT 1',
            ['id' => $id]
        ) !== null;
    }
}
