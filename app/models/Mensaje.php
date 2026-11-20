<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Mensaje extends Model
{
    private const COLUMNAS = 'm.mensaje_id, m.remitente_id, m.destinatario_id, m.curso_id,
        m.asunto, m.cuerpo, m.leido, m.fecha_creacion, m.fecha_leido,
        rem.nombre AS remitente_nombre, dest.nombre AS destinatario_nombre,
        c.titulo AS curso_titulo';

    private const JOINS = 'FROM mensajes m
        INNER JOIN usuarios rem ON rem.usuario_id = m.remitente_id
        INNER JOIN usuarios dest ON dest.usuario_id = m.destinatario_id
        LEFT JOIN cursos c ON c.curso_id = m.curso_id';

    public function listarBandeja(int $usuarioId, string $buzon, int $limite, int $desplazamiento): array
    {
        $columna = $buzon === 'enviados' ? 'm.remitente_id' : 'm.destinatario_id';
        $sql = 'SELECT ' . self::COLUMNAS . ' ' . self::JOINS . '
                WHERE ' . $columna . ' = :usuario_id
                ORDER BY m.fecha_creacion DESC, m.mensaje_id DESC
                LIMIT :limite OFFSET :desplazamiento';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $statement->bindValue(':limite', $limite, PDO::PARAM_INT);
        $statement->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
        $statement->execute();

        return array_map(static function (array $fila): array {
            $fila['mensaje_id'] = (int) $fila['mensaje_id'];
            $fila['remitente_id'] = (int) $fila['remitente_id'];
            $fila['destinatario_id'] = (int) $fila['destinatario_id'];
            $fila['curso_id'] = $fila['curso_id'] === null ? null : (int) $fila['curso_id'];
            $fila['leido'] = (bool) $fila['leido'];

            return $fila;
        }, $statement->fetchAll());
    }

    public function contarBandeja(int $usuarioId, string $buzon): int
    {
        $columna = $buzon === 'enviados' ? 'remitente_id' : 'destinatario_id';
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM mensajes WHERE ' . $columna . ' = :usuario_id',
            ['usuario_id' => $usuarioId]
        );

        return $fila ? (int) $fila['total'] : 0;
    }

    public function contarNoLeidos(int $usuarioId): int
    {
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM mensajes WHERE destinatario_id = :usuario_id AND leido = 0',
            ['usuario_id' => $usuarioId]
        );

        return $fila ? (int) $fila['total'] : 0;
    }

    public function listarDestinatarios(array $roles, int $excluirId): array
    {
        if ($roles === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($roles), '?'));
        $parametros = array_values($roles);
        $parametros[] = $excluirId;

        return $this->fetchAll(
            'SELECT usuario_id, nombre, correo, rol
             FROM usuarios
             WHERE rol IN (' . $marcadores . ')
               AND usuario_id <> ?
               AND bloqueado = 0
             ORDER BY nombre ASC, usuario_id ASC',
            $parametros
        );
    }

    public function buscarPorId(int $mensajeId): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::COLUMNAS . ' ' . self::JOINS . '
             WHERE m.mensaje_id = :id LIMIT 1',
            ['id' => $mensajeId]
        );
    }

    public function crear(
        int $remitenteId,
        int $destinatarioId,
        ?int $cursoId,
        string $asunto,
        string $cuerpo
    ): int {
        $this->execute(
            'INSERT INTO mensajes (remitente_id, destinatario_id, curso_id, asunto, cuerpo)
             VALUES (:remitente_id, :destinatario_id, :curso_id, :asunto, :cuerpo)',
            [
                'remitente_id' => $remitenteId,
                'destinatario_id' => $destinatarioId,
                'curso_id' => $cursoId,
                'asunto' => $asunto,
                'cuerpo' => $cuerpo,
            ]
        );

        return $this->lastInsertId();
    }

    public function marcarLeido(int $mensajeId, int $usuarioId): bool
    {
        return $this->execute(
            'UPDATE mensajes
             SET leido = 1, fecha_leido = NOW()
             WHERE mensaje_id = :id AND destinatario_id = :usuario_id AND leido = 0',
            [
                'id' => $mensajeId,
                'usuario_id' => $usuarioId,
            ]
        );
    }
}
