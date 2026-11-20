<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Services\ComentarioService;
use App\Services\CourseService;

class ModeracionController extends Controller
{
    public function __construct(
        private readonly ComentarioService $comentarios,
        private readonly CourseService $courses
    ) {
    }

    public function index(): void
    {
        $admin = Auth::user();

        if ($admin === null || $admin['rol'] !== 'admin') {
            $this->redirect('dashboard');
            return;
        }

        $resultado = $this->comentarios->listarModeracion($_GET);

        $this->view('admin/comentarios', [
            'admin' => $admin,
            'comentarios' => $resultado['ok'] ? $resultado['data'] : [],
            'errores' => $resultado['ok'] ? [] : ($resultado['detalles'] ?? []),
            'filtros' => [
                'curso_id' => is_scalar($_GET['curso_id'] ?? null) ? (string) $_GET['curso_id'] : '',
                'incluir_eliminados' => in_array($_GET['incluir_eliminados'] ?? '', ['1', 'true'], true) ? '1' : '',
            ],
            'cursos' => $this->courses->listarGestion(null, 1, 50)['data'],
            'flash' => $this->takeFlash(),
        ]);
    }
}
