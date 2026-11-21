<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface CategoriaRepositoryInterface
{
    public function listar(): array;

    public function existe(int $categoriaId): bool;

    public function listarGestion(): array;

    public function buscarPorId(int $categoriaId): ?array;

    public function existeNombre(string $nombre, ?int $excluirId = null): bool;

    public function crear(string $nombre, ?string $descripcion, int $creadoPor): int;

    public function actualizar(int $categoriaId, string $nombre, ?string $descripcion): bool;

    public function tieneCursos(int $categoriaId): bool;

    public function eliminar(int $categoriaId): bool;
}
