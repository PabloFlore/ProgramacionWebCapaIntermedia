<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\ComentarioRepositoryInterface;

class Comentario extends Model implements ComentarioRepositoryInterface
{
    public function listarPorCurso(int $cursoId): array
        {
            $filas = $this->fetchAll(
                'SELECT com.comentario_id, com.calificacion,
                    CASE WHEN com.eliminado_por_admin = 1 THEN NULL ELSE com.comentario END AS comentario,
                    com.eliminado_por_admin, com.fecha_comentario,
                    u.nombre AS estudiante_nombre
             FROM comentarios com
             INNER JOIN usuarios u ON u.usuario_id = com.estudiante_id
             WHERE com.curso_id = :curso_id
             ORDER BY com.fecha_comentario DESC, com.comentario_id DESC',
            ['curso_id' => $cursoId]
        );

        return array_map(static function (array $fila): array {
            $fila['comentario_id'] = (int) $fila['comentario_id'];
            $fila['calificacion'] = (int) $fila['calificacion'];
            $fila['eliminado_por_admin'] = (bool) $fila['eliminado_por_admin'];

            return $fila;
        }, $filas);
    }

    public function resumenCurso(int $cursoId): array
    {
        $fila = $this->fetchOne(
            'SELECT ROUND(AVG(calificacion), 2) AS promedio, COUNT(*) AS total
             FROM comentarios
             WHERE curso_id = :curso_id AND eliminado_por_admin = 0',
            ['curso_id' => $cursoId]
        );

        return [
            'calificacion_promedio' => $fila && $fila['promedio'] !== null ? (float) $fila['promedio'] : null,
            'total' => $fila ? (int) $fila['total'] : 0,
        ];
    }

    public function puedeComentar(int $cursoId, int $estudianteId): bool
    {
        $fila = $this->fetchOne(
            'SELECT i.inscripcion_id
             FROM inscripciones i
             WHERE i.curso_id = :curso_id AND i.estudiante_id = :estudiante_id AND i.completado = 1
             LIMIT 1',
            [
                'curso_id' => $cursoId,
                'estudiante_id' => $estudianteId,
            ]
        );

        return $fila !== null;
    }

    public function yaComento(int $cursoId, int $estudianteId): bool
    {
        $fila = $this->fetchOne(
            'SELECT comentario_id FROM comentarios
             WHERE curso_id = :curso_id AND estudiante_id = :estudiante_id
             LIMIT 1',
            [
                'curso_id' => $cursoId,
                'estudiante_id' => $estudianteId,
            ]
        );

        return $fila !== null;
    }

    public function crear(int $cursoId, int $estudianteId, int $calificacion, ?string $comentario): int
    {
        $this->execute(
            'INSERT INTO comentarios (curso_id, estudiante_id, calificacion, comentario)
             VALUES (:curso_id, :estudiante_id, :calificacion, :comentario)',
            [
                'curso_id' => $cursoId,
                'estudiante_id' => $estudianteId,
                'calificacion' => $calificacion,
                'comentario' => $comentario,
            ]
        );

        return $this->lastInsertId();
    }

    public function listarModeracion(?int $cursoId, bool $incluirEliminados): array
    {
        $sql = 'SELECT com.comentario_id, com.curso_id, com.estudiante_id, com.calificacion,
                       com.comentario, com.eliminado_por_admin, com.causa_eliminacion,
                       com.fecha_comentario, com.fecha_moderacion,
                       c.titulo AS curso_titulo, u.nombre AS estudiante_nombre,
                       m.nombre AS moderado_por_nombre
                FROM comentarios com
                INNER JOIN cursos c ON c.curso_id = com.curso_id
                INNER JOIN usuarios u ON u.usuario_id = com.estudiante_id
                LEFT JOIN usuarios m ON m.usuario_id = com.moderado_por
                WHERE 1 = 1';
        $params = [];

        if (!$incluirEliminados) {
            $sql .= ' AND com.eliminado_por_admin = 0';
        }

        if ($cursoId !== null) {
            $sql .= ' AND com.curso_id = :curso_id';
            $params['curso_id'] = $cursoId;
        }

        $sql .= ' ORDER BY com.fecha_comentario DESC, com.comentario_id DESC';

        return array_map(static function (array $fila): array {
            $fila['comentario_id'] = (int) $fila['comentario_id'];
            $fila['curso_id'] = (int) $fila['curso_id'];
            $fila['estudiante_id'] = (int) $fila['estudiante_id'];
            $fila['calificacion'] = (int) $fila['calificacion'];
            $fila['eliminado_por_admin'] = (bool) $fila['eliminado_por_admin'];

            return $fila;
        }, $this->fetchAll($sql, $params));
    }

    public function buscarPorId(int $comentarioId): ?array
    {
        return $this->fetchOne(
            'SELECT comentario_id, curso_id, eliminado_por_admin, causa_eliminacion
             FROM comentarios WHERE comentario_id = :id LIMIT 1',
            ['id' => $comentarioId]
        );
    }

    public function moderar(int $comentarioId, string $causa, int $moderadoPor): bool
    {
        return $this->execute(
            'UPDATE comentarios
             SET eliminado_por_admin = 1, causa_eliminacion = :causa,
                 moderado_por = :moderado_por, fecha_moderacion = NOW()
             WHERE comentario_id = :id',
            [
                'causa' => $causa,
                'moderado_por' => $moderadoPor,
                'id' => $comentarioId,
            ]
        );
    }
}
