<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\UserServiceInterface;

class AuthController extends Controller
{
    private AuthServiceInterface $authService;
    private UserServiceInterface $userService;

    public function __construct(AuthServiceInterface $authService, UserServiceInterface $userService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
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
        $correo = Request::string('correo', $_POST);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->view('auth/login', [
                'errors' => ['La sesión expiró. Intenta de nuevo.'],
                'old' => ['correo' => $correo],
                'flash' => $this->takeFlash(),
            ]);
            return;
        }

        $resultado = $this->authService->login($correo, Request::raw('contrasena', $_POST));

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

    public function verificarCorreo(): void
    {
        $token = Request::string('token', $_GET);
        $resultado = $this->authService->verificarCorreo($token);

        $this->flash($resultado['ok'] ? 'success' : 'error', $resultado['message']);
        $this->redirect('login');
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
            'nombre' => Request::string('nombre', $_POST),
            'correo' => Request::string('correo', $_POST),
            'rol' => Request::string('rol', $_POST, 'estudiante'),
            'genero' => Request::string('genero', $_POST),
            'fecha_nacimiento' => Request::string('fecha_nacimiento', $_POST),
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
