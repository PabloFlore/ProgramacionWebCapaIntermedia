<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\Contracts\CategoriaRepositoryInterface;
use App\Services\Contracts\CourseServiceInterface;
use App\Services\Contracts\ReporteServiceInterface;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteServiceInterface $reportes,
        private readonly CategoriaRepositoryInterface $categorias,
        private readonly CourseServiceInterface $courses
    ) {
    }

    public function index(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        if (!Auth::hasRole(['instructor', 'admin'])) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
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
        $campos = ['desde', 'hasta', 'curso_id', 'categoria_id', 'estado_pago', 'rol', 'bloqueado', 'pagina', 'limite', 'tab'];
        $valores = [];

        foreach ($campos as $campo) {
            $valor = $_GET[$campo] ?? '';
            $valores[$campo] = is_scalar($valor) ? (string) $valor : '';
        }

        return $valores;
    }
}
