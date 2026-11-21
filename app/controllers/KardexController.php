<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\Contracts\CategoriaRepositoryInterface;
use App\Services\Contracts\KardexServiceInterface;

final class KardexController extends Controller
{
    public function __construct(
        private readonly KardexServiceInterface $kardex,
        private readonly CategoriaRepositoryInterface $categorias
    ) {
    }

    public function index(): void
    {
        if (!Auth::hasRole(['estudiante'])) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $filtros = [
            'desde' => is_string($_GET['desde'] ?? null) ? trim($_GET['desde']) : '',
            'hasta' => is_string($_GET['hasta'] ?? null) ? trim($_GET['hasta']) : '',
            'categoria_id' => is_string($_GET['categoria_id'] ?? null) ? trim($_GET['categoria_id']) : '',
            'terminados' => ($_GET['terminados'] ?? '') === '1' ? '1' : '',
            'activos' => ($_GET['activos'] ?? '') === '1' ? '1' : '',
        ];

        $usuarioId = Session::usuarioId();
        $datos = $usuarioId === null
            ? ['resumen' => ['total' => 0, 'completados' => 0, 'en_curso' => 0, 'pagadas' => 0], 'inscripciones' => []]
            : $this->kardex->obtener($usuarioId, $filtros);

        $this->view('kardex/index', [
            'kardex' => $datos,
            'categorias' => $this->categorias->listar(),
            'filtros' => $filtros,
            'flash' => $this->takeFlash(),
        ]);
    }
}