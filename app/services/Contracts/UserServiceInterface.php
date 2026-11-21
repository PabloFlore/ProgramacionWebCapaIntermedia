<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface UserServiceInterface
{
    public function perfil(int $usuarioId): ?array;

    public function listar(?string $rol = null, ?bool $bloqueado = null, array $opciones = []): array;

    public function contarListado(?string $rol = null, ?bool $bloqueado = null, string $q = ''): int;

    public function actualizarPerfil(int $usuarioId, array $datos, ?array $archivo = null): array;

    public function actualizarFoto(int $usuarioId, array $archivo): array;

    public function bloquear(int $adminId, int $usuarioId, ?string $motivo): array;

    public function reactivar(int $usuarioId): array;
}
