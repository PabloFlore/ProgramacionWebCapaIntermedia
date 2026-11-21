<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface AuthServiceInterface
{
    public function registro(array $datos): array;

    public function login(string $correo, string $contrasena): array;

    public function cambiarContrasena(
        int $usuarioId,
        string $contrasenaActual,
        string $contrasenaNueva,
        string $confirmacion
    ): array;

    public function verificarCorreo(string $token): array;

    public function reenviarVerificacion(int $usuarioId): array;
}
