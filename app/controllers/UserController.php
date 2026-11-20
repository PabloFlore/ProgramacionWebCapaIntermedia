<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Services\UserService;

class UserController extends Controller
{
    private const ORDENES_USUARIOS = ['usuario_id', 'nombre', 'correo', 'rol', 'bloqueado', 'fecha_registro'];

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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

        $opciones = $this->opcionesListado();
        $total = $this->userService->contarListado($rol, $bloqueado, $opciones['q']);

        $this->view('admin/usuarios', [
            'admin' => $admin,
            'usuarios' => $this->userService->listar($rol, $bloqueado, $opciones),
            'filtroRol' => $rol,
            'filtroBloqueado' => $filtroBloqueado,
            'filtroQ' => $opciones['q'],
            'opciones' => $opciones,
            'total' => $total,
            'paginas' => $total === 0 ? 0 : (int) ceil($total / $opciones['limite']),
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

    private function opcionesListado(): array
    {
        $q = Request::string('q', $_GET);

        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        $orden = Request::string('orden', $_GET, 'usuario_id');

        if ($orden === '' || !in_array($orden, self::ORDENES_USUARIOS, true)) {
            $orden = 'usuario_id';
        }

        $direccion = strtolower(Request::string('direccion', $_GET, 'desc'));

        if (!in_array($direccion, ['asc', 'desc'], true)) {
            $direccion = 'desc';
        }

        $pagina = Request::int('pagina', $_GET, 1) ?? 1;

        if ($pagina < 1) {
            $pagina = 1;
        }

        $limite = Request::int('limite', $_GET, 25) ?? 25;

        if ($limite < 1 || $limite > 100) {
            $limite = 25;
        }

        return [
            'q' => $q,
            'orden' => $orden,
            'direccion' => $direccion,
            'pagina' => $pagina,
            'limite' => $limite,
        ];
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
