<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface MensajeRepositoryInterface
{
    public function listarBandeja(int $usuarioId, string $buzon, int $limite, int $desplazamiento): array;

    public function contarBandeja(int $usuarioId, string $buzon): int;

    public function contarNoLeidos(int $usuarioId): int;

    public function listarDestinatarios(array $roles, int $excluirId): array;

    public function buscarPorId(int $mensajeId): ?array;

    public function crear(
        int $remitenteId,
        int $destinatarioId,
        ?int $cursoId,
        string $asunto,
        string $cuerpo
    ): int;

    public function marcarLeido(int $mensajeId, int $usuarioId): bool;
}
