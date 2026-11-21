<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface ComentarioServiceInterface
{
    public function listarPorCurso(int $cursoId): array;

    public function crear(int $cursoId, array $datos, int $estudianteId): array;

    public function listarModeracion(array $filtros): array;

    public function eliminar(int $comentarioId, array $datos, int $adminId): array;
}
