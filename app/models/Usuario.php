<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Usuario extends Model
{
    private const CAMPOS_PUBLICOS = 'usuario_id, nombre, correo, rol, genero, fecha_nacimiento,
        foto_perfil, correo_verificado, intentos_fallidos, bloqueado, motivo_bloqueo,
        fecha_bloqueo, bloqueado_por, fecha_registro';

    public function buscarPorCorreo(string $correo): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::CAMPOS_PUBLICOS . ', contrasena_hash
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1',
            ['correo' => $correo]
        );
    }

    public function buscarPorCorreoParaActualizar(string $correo): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::CAMPOS_PUBLICOS . ', contrasena_hash
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1
             FOR UPDATE',
            ['correo' => $correo]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::CAMPOS_PUBLICOS . '
             FROM usuarios
             WHERE usuario_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function buscarPorIdConHash(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT ' . self::CAMPOS_PUBLICOS . ', contrasena_hash
             FROM usuarios
             WHERE usuario_id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function existeCorreo(string $correo, ?int $excluirId = null): bool
    {
        $sql = 'SELECT usuario_id FROM usuarios WHERE correo = :correo';
        $params = ['correo' => $correo];

        if ($excluirId !== null) {
            $sql .= ' AND usuario_id <> :excluir_id';
            $params['excluir_id'] = $excluirId;
        }

        $sql .= ' LIMIT 1';

        return $this->fetchOne($sql, $params) !== null;
    }

    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO usuarios (
                    nombre, correo, contrasena_hash, rol, genero, fecha_nacimiento,
                    foto_perfil, correo_verificado
                ) VALUES (
                    :nombre, :correo, :contrasena_hash, :rol, :genero, :fecha_nacimiento,
                    :foto_perfil, :correo_verificado
                )';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $statement->bindValue(':correo', $datos['correo'], PDO::PARAM_STR);
        $statement->bindValue(':contrasena_hash', $datos['contrasena_hash'], PDO::PARAM_STR);
        $statement->bindValue(':rol', $datos['rol'], PDO::PARAM_STR);
        $statement->bindValue(
            ':genero',
            $datos['genero'] ?? null,
            isset($datos['genero']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':fecha_nacimiento',
            $datos['fecha_nacimiento'] ?? null,
            isset($datos['fecha_nacimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':foto_perfil',
            $datos['foto_perfil'] ?? null,
            isset($datos['foto_perfil']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(':correo_verificado', (int) ($datos['correo_verificado'] ?? 0), PDO::PARAM_INT);
        $statement->execute();

        return $this->lastInsertId();
    }

    public function actualizarPerfil(int $id, array $datos): bool
    {
        $sql = 'UPDATE usuarios
                SET nombre = :nombre,
                    genero = :genero,
                    fecha_nacimiento = :fecha_nacimiento,
                    foto_perfil = :foto_perfil
                WHERE usuario_id = :id';

        $statement = $this->db->prepare($sql);
        $statement->bindValue(':nombre', $datos['nombre'], PDO::PARAM_STR);
        $statement->bindValue(
            ':genero',
            $datos['genero'] ?? null,
            isset($datos['genero']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':fecha_nacimiento',
            $datos['fecha_nacimiento'] ?? null,
            isset($datos['fecha_nacimiento']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(
            ':foto_perfil',
            $datos['foto_perfil'] ?? null,
            isset($datos['foto_perfil']) ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function actualizarContrasena(int $id, string $contrasenaHash): bool
    {
        return $this->execute(
            'UPDATE usuarios SET contrasena_hash = :contrasena_hash WHERE usuario_id = :id',
            ['contrasena_hash' => $contrasenaHash, 'id' => $id]
        );
    }

    public function actualizarIntentos(int $id, int $intentos, ?string $motivoBloqueo = null): bool
    {
        if ($motivoBloqueo !== null) {
            return $this->execute(
                'UPDATE usuarios
                 SET intentos_fallidos = :intentos,
                     bloqueado = 1,
                     motivo_bloqueo = :motivo,
                     fecha_bloqueo = NOW()
                 WHERE usuario_id = :id',
                ['intentos' => $intentos, 'motivo' => $motivoBloqueo, 'id' => $id]
            );
        }

        return $this->execute(
            'UPDATE usuarios SET intentos_fallidos = :intentos WHERE usuario_id = :id',
            ['intentos' => $intentos, 'id' => $id]
        );
    }

    public function reiniciarIntentos(int $id): bool
    {
        return $this->execute(
            'UPDATE usuarios SET intentos_fallidos = 0 WHERE usuario_id = :id',
            ['id' => $id]
        );
    }

    public function listar(?string $rol = null, ?bool $bloqueado = null): array
    {
        $sql = 'SELECT ' . self::CAMPOS_PUBLICOS . ' FROM usuarios WHERE 1 = 1';
        $params = [];

        if ($rol !== null) {
            $sql .= ' AND rol = :rol';
            $params['rol'] = $rol;
        }

        if ($bloqueado !== null) {
            $sql .= ' AND bloqueado = :bloqueado';
            $params['bloqueado'] = $bloqueado ? 1 : 0;
        }

        $sql .= ' ORDER BY usuario_id ASC';

        return $this->fetchAll($sql, $params);
    }

    public function bloquear(int $id, string $motivo, int $adminId): bool
    {
        return $this->execute(
            'UPDATE usuarios
             SET bloqueado = 1,
                 motivo_bloqueo = :motivo,
                 fecha_bloqueo = NOW(),
                 bloqueado_por = :admin_id
             WHERE usuario_id = :id',
            ['motivo' => $motivo, 'admin_id' => $adminId, 'id' => $id]
        );
    }

    public function reactivar(int $id): bool
    {
        return $this->execute(
            'UPDATE usuarios
             SET bloqueado = 0,
                 intentos_fallidos = 0,
                 motivo_bloqueo = NULL,
                 fecha_bloqueo = NULL,
                 bloqueado_por = NULL
             WHERE usuario_id = :id',
            ['id' => $id]
        );
    }

    public function contarAdminsActivos(?int $excluirId = null): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM usuarios
                WHERE rol = 'admin' AND bloqueado = 0";
        $params = [];

        if ($excluirId !== null) {
            $sql .= ' AND usuario_id <> :excluir_id';
            $params['excluir_id'] = $excluirId;
        }

        $row = $this->fetchOne($sql, $params);

        return $row ? (int) $row['total'] : 0;
    }
}
