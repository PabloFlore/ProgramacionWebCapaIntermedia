<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Services\Contracts\KardexServiceInterface;

final class KardexController extends Controller
{
    public function __construct(private readonly KardexServiceInterface $kardex)
    {
    }

    public function index(): void
    {
        if (!Auth::hasRole(['estudiante'])) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $usuarioId = Session::usuarioId();
        $datos = $usuarioId === null
            ? ['resumen' => ['total' => 0, 'completados' => 0, 'en_curso' => 0, 'pagadas' => 0], 'inscripciones' => []]
            : $this->kardex->obtener($usuarioId);

        $this->view('kardex/index', [
            'kardex' => $datos,
            'flash' => $this->takeFlash(),
        ]);
    }
}
