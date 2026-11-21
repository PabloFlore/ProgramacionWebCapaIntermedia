<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Services\Contracts\CourseServiceInterface;

final class CourseController extends Controller
{
    public function __construct(private readonly CourseServiceInterface $courses)
    {
    }

    public function show(string $id): void
    {
        $cursoId = ctype_digit($id) ? (int) $id : 0;
        $curso = $cursoId > 0 ? $this->courses->detallePublico($cursoId) : null;

        if ($curso === null) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        $this->view('course/show', ['curso' => $curso, 'flash' => $this->takeFlash()]);
    }

    public function gestion(): void
    {
        if (!Auth::hasRole(['instructor', 'admin'])) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            return;
        }

        $this->view('course/gestion', ['flash' => $this->takeFlash()]);
    }
}
