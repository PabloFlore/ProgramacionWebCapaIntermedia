<?php

declare(strict_types=1);

namespace App\Models;

class Categoria extends Model
{
    public function listar(): array
    {
        $rows = $this->fetchAll(
            'SELECT categoria_id, nombre, descripcion
             FROM categorias
             ORDER BY nombre ASC'
        );

        return array_map(static function (array $categoria): array {
            $categoria['categoria_id'] = (int) $categoria['categoria_id'];

            return $categoria;
        }, $rows);
    }

    public function existe(int $categoriaId): bool
    {
        $row = $this->fetchOne(
            'SELECT categoria_id FROM categorias WHERE categoria_id = :id LIMIT 1',
            ['id' => $categoriaId]
        );

        return $row !== null;
    }
}
