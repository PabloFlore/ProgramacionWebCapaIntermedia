<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\ProgresoNivelRepositoryInterface;

class ProgresoNivel extends Model implements ProgresoNivelRepositoryInterface
{
    public function asegurar(int $inscripcionId, int $nivelId): bool
    {
        return $this->db->prepare(
            'INSERT IGNORE INTO progreso_niveles (inscripcion_id, nivel_id, completado)
             VALUES (:inscripcion_id, :nivel_id, FALSE)'
        )->execute([
            'inscripcion_id' => $inscripcionId,
            'nivel_id' => $nivelId,
        ]);
    }

    public function marcarCompletado(int $inscripcionId, int $nivelId): bool
    {
        return $this->db->prepare(
            'UPDATE progreso_niveles
             SET completado = TRUE, fecha_completado = COALESCE(fecha_completado, NOW())
             WHERE inscripcion_id = :inscripcion_id AND nivel_id = :nivel_id'
        )->execute([
            'inscripcion_id' => $inscripcionId,
            'nivel_id' => $nivelId,
        ]);
    }

    public function contarCompletados(int $inscripcionId): int
    {
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM progreso_niveles
             WHERE inscripcion_id = :id AND completado = TRUE',
            ['id' => $inscripcionId]
        );

        return $fila ? (int) $fila['total'] : 0;
    }

    public function listarPorInscripcion(int $inscripcionId): array
    {
        return $this->fetchAll(
            'SELECT n.nivel_id, n.titulo, n.orden, pn.completado, pn.fecha_completado
             FROM progreso_niveles pn
             INNER JOIN niveles n ON n.nivel_id = pn.nivel_id
             WHERE pn.inscripcion_id = :id
             ORDER BY n.orden ASC',
            ['id' => $inscripcionId]
        );
    }
}
