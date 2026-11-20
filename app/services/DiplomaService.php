<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Diploma;

final class DiplomaService
{
    public function __construct(private readonly Diploma $diplomas)
    {
    }

    public function emitir(int $inscripcionId): array
    {
        $existente = $this->diplomas->buscarPorInscripcion($inscripcionId);

        if ($existente !== null) {
            return $existente;
        }

        $anio = date('Y');
        $aleatorio = strtoupper(bin2hex(random_bytes(4)));
        $codigo = 'SOSCUR-' . $anio . '-' . $aleatorio;
        $this->diplomas->crear($inscripcionId, $codigo);

        return $this->diplomas->buscarPorInscripcion($inscripcionId) ?? [
            'inscripcion_id' => $inscripcionId,
            'codigo_verificacion' => $codigo,
        ];
    }

    public function porInscripcion(int $inscripcionId): ?array
    {
        return $this->diplomas->buscarPorInscripcion($inscripcionId);
    }

    public function verificar(string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));

        if ($codigo === '') {
            return null;
        }

        return $this->diplomas->buscarPorCodigo($codigo);
    }
}
