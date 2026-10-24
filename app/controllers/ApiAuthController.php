<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\AuthService;
use App\Services\UserService;

class ApiAuthController extends JsonController
{
    private AuthService $authService;
    private UserService $userService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userService = new UserService();
    }

    public function registro(): void
    {
        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->authService->registro($payload);

        if (!$resultado['ok']) {
            $extra = isset($resultado['detalles']) ? ['detalles' => $resultado['detalles']] : [];
            $this->error((int) $resultado['status'], (string) $resultado['message'], $extra);
            return;
        }

        $usuario = $resultado['usuario'];
        Session::login((int) $usuario['usuario_id'], $usuario['rol'], $usuario['nombre']);

        $this->respond([
            'mensaje' => $resultado['message'],
            'usuario' => $this->usuarioPublico($usuario),
        ], 201);
    }

    public function login(): void
    {
        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $correo = isset($payload['correo']) && is_string($payload['correo']) ? $payload['correo'] : '';
        $contrasena = isset($payload['contrasena']) && is_string($payload['contrasena'])
            ? $payload['contrasena']
            : '';

        $resultado = $this->authService->login($correo, $contrasena);

        if (!$resultado['ok']) {
            $this->error((int) $resultado['status'], (string) $resultado['message']);
            return;
        }

        $usuario = $resultado['usuario'];
        Session::login((int) $usuario['usuario_id'], $usuario['rol'], $usuario['nombre']);

        $this->respond([
            'mensaje' => 'Sesión iniciada',
            'usuario' => $this->usuarioPublico($usuario),
        ], 200);
    }

    public function logout(): void
    {
        Session::destroy();
        $this->respond(['mensaje' => 'Sesión cerrada'], 200);
    }

    public function perfil(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $this->respond(['usuario' => $this->usuarioPublico($usuario)], 200);
    }

    public function actualizarPerfil(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->userService->actualizarPerfil(
            (int) $usuario['usuario_id'],
            $payload
        );

        if (!$resultado['ok']) {
            $extra = isset($resultado['detalles']) ? ['detalles' => $resultado['detalles']] : [];
            $this->error((int) $resultado['status'], (string) $resultado['message'], $extra);
            return;
        }

        $this->respond([
            'mensaje' => $resultado['message'],
            'usuario' => $this->usuarioPublico($resultado['usuario']),
        ], 200);
    }

    public function cambiarContrasena(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $resultado = $this->authService->cambiarContrasena(
            (int) $usuario['usuario_id'],
            (string) ($payload['contrasena_actual'] ?? ''),
            (string) ($payload['contrasena_nueva'] ?? ''),
            (string) ($payload['contrasena_confirmacion'] ?? '')
        );

        if (!$resultado['ok']) {
            $extra = isset($resultado['detalles']) ? ['detalles' => $resultado['detalles']] : [];
            $this->error((int) $resultado['status'], (string) $resultado['message'], $extra);
            return;
        }

        $this->respond(['mensaje' => $resultado['message']], 200);
    }

    public function subirFoto(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        if (!isset($_FILES['foto_perfil'])
            || !is_array($_FILES['foto_perfil'])
            || ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) {
            $this->error(400, 'Debes enviar una imagen en el campo foto_perfil');
            return;
        }

        $resultado = $this->userService->actualizarFoto(
            (int) $usuario['usuario_id'],
            $_FILES['foto_perfil']
        );

        if (!$resultado['ok']) {
            $extra = isset($resultado['detalles']) ? ['detalles' => $resultado['detalles']] : [];
            $this->error((int) $resultado['status'], (string) $resultado['message'], $extra);
            return;
        }

        $this->respond([
            'mensaje' => $resultado['message'],
            'usuario' => $this->usuarioPublico($resultado['usuario']),
        ], 200);
    }

    private function usuarioPublico(array $usuario): array
    {
        unset($usuario['contrasena_hash']);

        return $usuario;
    }
}
