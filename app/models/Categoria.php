<?php

declare(strict_types=1);

namespace App\Models;

class Categoria extends Model
{
    public function existe(int $categoriaId): bool
    {
        $row = $this->fetchOne(
            'SELECT categoria_id FROM categorias WHERE categoria_id = :id LIMIT 1',
            ['id' => $categoriaId]
        );

        return $row !== null;
    }
}
