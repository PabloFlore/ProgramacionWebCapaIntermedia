<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\CourseService;

final class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses)
    {
    }

    public function show(string $id): void
    {
        $cursoId = ctype_digit($id) ? (int) $id : 0;
        $curso = $cursoId > 0 ? $this->courses->detalle($cursoId) : null;

        if ($curso === null) {
            $this->flash('error', 'El curso solicitado no existe o ya no está disponible.');
            $this->redirect('');
        }

        $this->view('course/show', ['curso' => $curso, 'flash' => $this->takeFlash()]);
    }

    public function gestion(): void
    {
        $this->view('course/gestion', ['flash' => $this->takeFlash()]);
    }
}
