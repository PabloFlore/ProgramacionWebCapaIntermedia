<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Services\UserService;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(): void
    {
        $admin = $this->admin();

        if ($admin === null) {
            return;
        }

        $rol = isset($_GET['rol']) && in_array($_GET['rol'], ['estudiante', 'instructor', 'admin'], true)
            ? $_GET['rol']
            : null;

        $bloqueado = null;
        $filtroBloqueado = '';

        if (isset($_GET['bloqueado']) && is_string($_GET['bloqueado']) && $_GET['bloqueado'] !== '') {
            $filtroBloqueado = $_GET['bloqueado'];
            $bloqueado = filter_var($filtroBloqueado, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $this->view('admin/usuarios', [
            'admin' => $admin,
            'usuarios' => $this->userService->listar($rol, $bloqueado),
            'filtroRol' => $rol,
            'filtroBloqueado' => $filtroBloqueado,
            'flash' => $this->takeFlash(),
        ]);
    }

    public function bloquear(string $id): void
    {
        $admin = $this->admin();

        if ($admin === null) {
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirect('admin/usuarios');
            return;
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->flash('error', 'Identificador de usuario inválido.');
            $this->redirect('admin/usuarios');
            return;
        }

        $resultado = $this->userService->bloquear(
            (int) $admin['usuario_id'],
            (int) $id,
            (string) ($_POST['motivo'] ?? '')
        );

        $this->flash($resultado['ok'] ? 'success' : 'error', $resultado['message']);
        $this->redirect('admin/usuarios');
    }

    public function reactivar(string $id): void
    {
        if ($this->admin() === null) {
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirect('admin/usuarios');
            return;
        }

        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->flash('error', 'Identificador de usuario inválido.');
            $this->redirect('admin/usuarios');
            return;
        }

        $resultado = $this->userService->reactivar((int) $id);

        $this->flash($resultado['ok'] ? 'success' : 'error', $resultado['message']);
        $this->redirect('admin/usuarios');
    }

    private function admin(): ?array
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return null;
        }

        if ($usuario['rol'] !== 'admin') {
            http_response_code(403);
            $this->view('errors/403');
            return null;
        }

        return $usuario;
    }
}
