<?php

declare(strict_types=1);

namespace App\Models;

class Diploma extends Model
{
    public function buscarPorInscripcion(int $inscripcionId): ?array
    {
        return $this->fetchOne(
            'SELECT d.diploma_id, d.codigo_verificacion, d.fecha_emision, i.inscripcion_id,
                    i.estudiante_id, i.fecha_completado, c.curso_id, c.titulo AS curso_titulo,
                    u.nombre AS estudiante_nombre
             FROM diplomas d
             INNER JOIN inscripciones i ON i.inscripcion_id = d.inscripcion_id
             INNER JOIN cursos c ON c.curso_id = i.curso_id
             INNER JOIN usuarios u ON u.usuario_id = i.estudiante_id
             WHERE d.inscripcion_id = :id
             LIMIT 1',
            ['id' => $inscripcionId]
        );
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->fetchOne(
            'SELECT d.codigo_verificacion, d.fecha_emision, c.titulo AS curso_titulo,
                    u.nombre AS estudiante_nombre
             FROM diplomas d
             INNER JOIN inscripciones i ON i.inscripcion_id = d.inscripcion_id
             INNER JOIN cursos c ON c.curso_id = i.curso_id
             INNER JOIN usuarios u ON u.usuario_id = i.estudiante_id
             WHERE d.codigo_verificacion = :codigo
             LIMIT 1',
            ['codigo' => $codigo]
        );
    }

    public function crear(int $inscripcionId, string $codigo): int
    {
        $this->db->prepare(
            'INSERT INTO diplomas (inscripcion_id, codigo_verificacion)
             VALUES (:inscripcion_id, :codigo)'
        )->execute([
            'inscripcion_id' => $inscripcionId,
            'codigo' => $codigo,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
