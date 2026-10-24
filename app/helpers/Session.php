<?php

declare(strict_types=1);

namespace App\Helpers;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(int $usuarioId, string $rol, string $nombre): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuarioId;
        $_SESSION['rol'] = $rol;
        $_SESSION['nombre'] = $nombre;
    }

    public static function usuarioId(): ?int
    {
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    public static function rol(): ?string
    {
        return $_SESSION['rol'] ?? null;
    }

    public static function nombre(): ?string
    {
        return $_SESSION['nombre'] ?? null;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
