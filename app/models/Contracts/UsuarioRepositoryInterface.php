<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface UsuarioRepositoryInterface
{
    public function buscarPorCorreo(string $correo): ?array;

    public function buscarPorCorreoParaActualizar(string $correo): ?array;

    public function buscarPorId(int $id): ?array;

    public function buscarPorIdConHash(int $id): ?array;

    public function existeCorreo(string $correo, ?int $excluirId = null): bool;

    public function crear(array $datos): int;

    public function actualizarPerfil(int $id, array $datos): bool;

    public function actualizarContrasena(int $id, string $contrasenaHash): bool;

    public function actualizarIntentos(int $id, int $intentos, ?string $motivoBloqueo = null): bool;

    public function reiniciarIntentos(int $id): bool;

    public function listar(?string $rol = null, ?bool $bloqueado = null): array;

    public function bloquear(int $id, string $motivo, int $adminId): bool;

    public function reactivar(int $id): bool;

    public function contarAdminsActivos(?int $excluirId = null): int;

    public function buscarPorTokenVerificacion(string $tokenHash): ?array;

    public function buscarVerificacionPorId(int $id): ?array;

    public function marcarCorreoVerificado(int $id): bool;

    public function actualizarTokenVerificacion(int $id, string $tokenHash, string $expira): bool;
}
