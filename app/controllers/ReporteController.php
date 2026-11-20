<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\Categoria;
use App\Services\CourseService;
use App\Services\ReporteService;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reportes,
        private readonly Categoria $categorias,
        private readonly CourseService $courses
    ) {
    }

    public function index(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        $instructorId = $usuario['rol'] === 'admin' ? null : (int) ($usuario['usuario_id'] ?? Session::usuarioId());
        $resultado = $this->reportes->generar(
            $_GET,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) $usuario['rol']
        );

        $this->view('reporte/index', [
            'usuario' => $usuario,
            'reporte' => $resultado['ok'] ? $resultado['reporte'] : null,
            'errores' => $resultado['ok'] ? [] : ($resultado['detalles'] ?? []),
            'filtros' => $this->valorFiltros(),
            'categorias' => $this->categorias->listar(),
            'cursos' => $this->courses->listarGestion($instructorId, 1, 50)['data'],
            'flash' => $this->takeFlash(),
        ]);
    }

    private function valorFiltros(): array
    {
        $campos = ['desde', 'hasta', 'curso_id', 'categoria_id', 'estado_pago', 'rol', 'bloqueado', 'pagina', 'limite'];
        $valores = [];

        foreach ($campos as $campo) {
            $valor = $_GET[$campo] ?? '';
            $valores[$campo] = is_scalar($valor) ? (string) $valor : '';
        }

        return $valores;
    }
}
