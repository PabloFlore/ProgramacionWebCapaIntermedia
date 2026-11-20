<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\ReporteService;

final class ApiReporteController extends JsonController
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function index(): void
    {
        $usuario = $this->requireRole(['instructor', 'admin']);

        if ($usuario === null) {
            return;
        }

        $resultado = $this->reportes->generar(
            $_GET,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        );

        if (!$resultado['ok']) {
            $this->error($resultado['status'], $resultado['message'], [
                'detalles' => $resultado['detalles'] ?? [],
            ]);
            return;
        }

        $this->respond(['data' => $resultado['reporte']]);
    }
}
