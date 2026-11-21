<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface MensajeServiceInterface
{
    public function listar(int $usuarioId, array $filtros): array;

    public function destinatarios(int $usuarioId, string $rol): array;

    public function crear(int $remitenteId, string $rol, array $datos): array;

    public function marcarLeido(int $mensajeId, int $usuarioId): array;
}
