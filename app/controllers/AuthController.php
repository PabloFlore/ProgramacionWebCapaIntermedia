<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Services\AuthService;
use App\Services\UserService;

class AuthController extends Controller
{
    private AuthService $authService;
    private UserService $userService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userService = new UserService();
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
            return;
        }

        $this->view('auth/login', ['flash' => $this->takeFlash()]);
    }

    public function login(): void
    {
        $correo = trim((string) ($_POST['correo'] ?? ''));

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->view('auth/login', [
                'errors' => ['La sesión expiró. Intenta de nuevo.'],
                'old' => ['correo' => $correo],
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $resultado = $this->authService->login($correo, (string) ($_POST['contrasena'] ?? ''));

        if (!$resultado['ok']) {
            $this->view('auth/login', [
                'errors' => [$resultado['message']],
                'old' => ['correo' => $correo],
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $usuario = $resultado['usuario'];
        Session::login((int) $usuario['usuario_id'], $usuario['rol'], $usuario['nombre']);
        $this->flash('success', 'Bienvenido, ' . $usuario['nombre'] . '.');
        $this->redirect('dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
            return;
        }

        $this->view('auth/register', ['flash' => $this->takeFlash()]);
    }

    public function register(): void
    {
        $old = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'correo' => trim((string) ($_POST['correo'] ?? '')),
            'rol' => (string) ($_POST['rol'] ?? 'estudiante'),
            'genero' => (string) ($_POST['genero'] ?? ''),
            'fecha_nacimiento' => (string) ($_POST['fecha_nacimiento'] ?? ''),
        ];

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->view('auth/register', [
                'errors' => ['La sesión expiró. Intenta de nuevo.'],
                'old' => $old,
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $fotoPath = null;

        if (isset($_FILES['foto_perfil'])
            && is_array($_FILES['foto_perfil'])
            && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {
            $subida = Upload::avatar($_FILES['foto_perfil']);

            if (!$subida['ok']) {
                $this->view('auth/register', [
                    'errors' => [$subida['error']],
                    'old' => $old,
                    'flash' => $this->takeFlash(),
                ]);
                return;
            }

            $fotoPath = $subida['path'];
        }

        $datos = $_POST;
        $datos['foto_perfil'] = $fotoPath;

        $resultado = $this->authService->registro($datos);

        if (!$resultado['ok']) {
            if ($fotoPath !== null) {
                Upload::borrar($fotoPath);
            }

            $this->view('auth/register', [
                'errors' => $resultado['detalles'] ?? [$resultado['message']],
                'old' => $old,
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $usuario = $resultado['usuario'];
        Session::login((int) $usuario['usuario_id'], $usuario['rol'], $usuario['nombre']);
        $this->flash('success', 'Tu cuenta fue creada correctamente.');
        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'No se pudo cerrar la sesión.');
            $this->redirect(Auth::check() ? 'dashboard' : 'login');
            return;
        }

        Session::destroy();
        $this->redirect('login');
    }

    public function dashboard(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        $this->view('dashboard/index', [
            'usuario' => $usuario,
            'flash' => $this->takeFlash(),
        ]);
    }

    public function showProfile(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        $this->view('profile/index', [
            'usuario' => $usuario,
            'flash' => $this->takeFlash(),
        ]);
    }

    public function updateProfile(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirect('perfil');
            return;
        }

        $resultado = $this->userService->actualizarPerfil(
            (int) $usuario['usuario_id'],
            $_POST,
            $_FILES['foto_perfil'] ?? null
        );

        if (!$resultado['ok']) {
            $this->view('profile/index', [
                'usuario' => $usuario,
                'errors' => $resultado['detalles'] ?? [$resultado['message']],
                'old' => $_POST,
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $this->flash('success', $resultado['message']);
        $this->redirect('perfil');
    }

    public function changePassword(): void
    {
        $usuario = Auth::user();

        if ($usuario === null) {
            $this->redirect('login');
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La sesión expiró. Intenta de nuevo.');
            $this->redirect('perfil');
            return;
        }

        $resultado = $this->authService->cambiarContrasena(
            (int) $usuario['usuario_id'],
            (string) ($_POST['contrasena_actual'] ?? ''),
            (string) ($_POST['contrasena_nueva'] ?? ''),
            (string) ($_POST['contrasena_confirmacion'] ?? '')
        );

        $this->flash($resultado['ok'] ? 'success' : 'error', $resultado['message']);
        $this->redirect('perfil');
    }
}
