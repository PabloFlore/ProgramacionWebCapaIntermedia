<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface ReporteRepositoryInterface
{
    public function resumenUsuarios(array $filtros, ?int $instructorId): array;

    public function usuariosPorRol(array $filtros, ?int $instructorId): array;

    public function contarCursos(array $filtros, ?int $instructorId): int;

    public function resumenVentas(array $filtros, ?int $instructorId): array;

    public function ventasPorCurso(array $filtros, ?int $instructorId): array;

    public function contarDetalle(array $filtros, ?int $instructorId): int;

    public function detalleVentas(array $filtros, ?int $instructorId, int $limite, int $offset): array;
}
