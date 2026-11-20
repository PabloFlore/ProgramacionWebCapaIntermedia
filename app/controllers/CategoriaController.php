<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Services\CategoriaService;

class CategoriaController extends Controller
{
    public function __construct(private readonly CategoriaService $categorias)
    {
    }

    public function index(): void
    {
        $admin = Auth::user();

        if ($admin === null || $admin['rol'] !== 'admin') {
            $this->redirect('dashboard');
            return;
        }

        $this->view('admin/categorias', [
            'admin' => $admin,
            'categorias' => $this->categorias->listar()['categorias'],
            'flash' => $this->takeFlash(),
        ]);
    }
}
