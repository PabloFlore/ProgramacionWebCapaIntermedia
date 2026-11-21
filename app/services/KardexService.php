<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\InscripcionRepositoryInterface;
use App\Services\Contracts\KardexServiceInterface;

final class KardexService implements KardexServiceInterface
{
    public function __construct(
        private readonly InscripcionRepositoryInterface $inscripciones
    ) {
    }

    public function obtener(int $estudianteId, array $filtros = []): array
    {
        $inscripciones = $this->inscripciones->listarKardex($estudianteId, $filtros);

        $completados = 0;
        $pagadas = 0;

        foreach ($inscripciones as $inscripcion) {
            if (!empty($inscripcion['completado'])) {
                $completados++;
            }

            if (($inscripcion['estado_pago'] ?? '') === 'pagado') {
                $pagadas++;
            }
        }

        return [
            'resumen' => [
                'total' => count($inscripciones),
                'completados' => $completados,
                'en_curso' => count($inscripciones) - $completados,
                'pagadas' => $pagadas,
            ],
            'inscripciones' => $inscripciones,
        ];
    }
}
