<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Models\Contracts\CursoRepositoryInterface;
use App\Services\Contracts\MensajeServiceInterface;

class MensajeController extends Controller
{
    public function __construct(
        private readonly MensajeServiceInterface $mensajes,
        private readonly CursoRepositoryInterface $cursos
    ) {
    }

    public function index(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        $usuarioId = (int) $usuario['usuario_id'];
        $rol = (string) $usuario['rol'];
        $resultado = $this->mensajes->listar($usuarioId, $_GET);
        $destinatarios = $this->mensajes->destinatarios($usuarioId, $rol);

        $this->view('mensajes/index', [
            'usuario' => $usuario,
            'mensajes' => $resultado['ok'] ? $resultado['data'] : [],
            'pagination' => $resultado['ok'] ? $resultado['pagination'] : [],
            'no_leidos' => $resultado['ok'] ? $resultado['no_leidos'] : 0,
            'destinatarios' => $destinatarios['ok'] ? $destinatarios['data'] : [],
            'cursos' => $this->cursos->listarActivos(),
            'filtros' => [
                'buzon' => ($_GET['buzon'] ?? 'recibidos') === 'enviados' ? 'enviados' : 'recibidos',
            ],
            'errores' => $resultado['ok'] ? [] : ($resultado['detalles'] ?? []),
            'flash' => $this->takeFlash(),
        ]);
    }
}
