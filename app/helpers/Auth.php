<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Usuario;

final class Auth
{
    public static function user(): ?array
    {
        $usuarioId = Session::usuarioId();

        if ($usuarioId === null) {
            return null;
        }

        $usuario = (new Usuario())->buscarPorId($usuarioId);

        if ($usuario === null || (int) $usuario['bloqueado'] === 1) {
            Session::clearUser();
            return null;
        }

        return $usuario;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $usuario = self::user();

        return $usuario === null ? null : (int) $usuario['usuario_id'];
    }

    public static function rol(): ?string
    {
        $usuario = self::user();

        return $usuario === null ? null : $usuario['rol'];
    }

    public static function hasRole(string|array $roles): bool
    {
        $rol = self::rol();

        if ($rol === null) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($rol, $roles, true);
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }
}
