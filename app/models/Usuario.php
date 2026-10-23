<?php

declare(strict_types=1);

namespace App\Models;

class Usuario extends Model
{
    public function buscarPorCorreo(string $correo): ?array
    {
        return $this->fetchOne(
            'SELECT usuario_id, nombre, correo, contrasena_hash, rol, bloqueado
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1',
            ['correo' => $correo]
        );
    }
}
