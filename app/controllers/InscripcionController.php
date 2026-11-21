<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Session;
use App\Services\Contracts\InscripcionServiceInterface;

final class InscripcionController extends Controller
{
    public function __construct(private readonly InscripcionServiceInterface $inscripciones)
    {
    }

    public function index(): void
    {
        $usuarioId = Session::usuarioId();
        $inscripciones = $usuarioId === null ? [] : $this->inscripciones->misInscripciones($usuarioId);
        $this->view('inscripcion/index', [
            'inscripciones' => $inscripciones,
            'flash' => $this->takeFlash(),
        ]);
    }

    public function diploma(string $inscripcionId): void
    {
        $usuarioId = Session::usuarioId();
        $id = ctype_digit($inscripcionId) ? (int) $inscripcionId : 0;
        $diploma = $usuarioId !== null && $id > 0
            ? $this->inscripciones->diplomaPorInscripcion($usuarioId, $id)
            : null;

        if ($diploma === null) {
            $this->flash('error', 'El certificado todavía no está disponible.');
            $this->redirect('mis-cursos');
        }

        $this->view('diploma/show', ['diploma' => $diploma, 'flash' => $this->takeFlash()]);
    }

    public function verificar(): void
    {
        $this->view('diploma/verificar', ['flash' => $this->takeFlash()]);
    }
}
