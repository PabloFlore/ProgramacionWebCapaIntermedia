<?php

declare(strict_types=1);

namespace App\Helpers;

final class Fecha
{
    private const MESES = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    public static function formato(mixed $valor, bool $conHora = false): string
    {
        if (!is_string($valor) || trim($valor) === '') {
            return '—';
        }

        $marca = strtotime($valor);

        if ($marca === false) {
            return '—';
        }

        $dia = date('d', $marca);
        $mes = self::MESES[(int) date('n', $marca)] ?? date('M', $marca);
        $anio = date('Y', $marca);

        $resultado = "{$dia}/{$mes}/{$anio}";

        return $conHora ? $resultado . ' ' . date('H:i', $marca) : $resultado;
    }
}