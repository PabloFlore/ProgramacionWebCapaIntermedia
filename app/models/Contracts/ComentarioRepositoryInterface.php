<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface ComentarioRepositoryInterface
{
    public function listarPorCurso(int $cursoId): array;

    public function resumenCurso(int $cursoId): array;

    public function puedeComentar(int $cursoId, int $estudianteId): bool;

    public function yaComento(int $cursoId, int $estudianteId): bool;

    public function crear(int $cursoId, int $estudianteId, int $calificacion, ?string $comentario): int;

    public function listarModeracion(?int $cursoId, bool $incluirEliminados): array;

    public function buscarPorId(int $comentarioId): ?array;

    public function moderar(int $comentarioId, string $causa, int $moderadoPor): bool;
}
