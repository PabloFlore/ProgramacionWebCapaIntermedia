<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Core\Request;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\UserServiceInterface;

class ApiAuthController extends JsonController
{
    private AuthServiceInterface $authService;
    private UserServiceInterface $userService;

    public function __construct(AuthServiceInterface $authService, UserServiceInterface $userService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
    }

    public function registro(): void
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $esFormulario = str_contains($contentType, 'multipart/form-data')
            || str_contains($contentType, 'application/x-www-form-urlencoded');
        $fotoPath = null;

        if ($esFormulario) {
            $payload = is_array($_POST) ? $_POST : [];

            if (isset($_FILES['foto_perfil'])
                && is_array($_FILES['foto_perfil'])
                && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            ) {
                $subida = Upload::avatar($_FILES['foto_perfil']);

                if (!$subida['ok']) {
                    $this->error(400, (string) $subida['error']);
                    return;
                }

                $fotoPath = $subida['path'];
            }

            $payload['foto_perfil'] = $fotoPath;
        } else {
            $payload = $this->readJsonBody();

            if ($payload === null) {
                $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
                return;
            }
        }

        $resultado = $this->authService->registro($payload);

        if (!$resultado['ok']) {
            if ($fotoPath !== null) {
                Upload::borrar($fotoPath);
            }

            $extra = isset($resultado['detalles']) ? ['detalles' => $resultado['detalles']] : [];
            $this->error((int) $resultado['status'], (string) $resultado['message'], $extra);
            return;
        }

        $usuario = $resultado['usuario'];
        Session::login((int) $usuario['usuario_id'], $usuario['rol'], $usuario['nombre']);

        $respuesta = [
            'mensaje' => $resultado['message'],
            'usuario' => $this->usuarioPublico($usuario),
            'correo_enviado' => (bool) ($resultado['correo_enviado'] ?? false),
        ];

        if (isset($resultado['token_verificacion'])) {
            $respuesta['token_verificacion'] = $resultado['token_verificacion'];
        }

        $this->respond($respuesta, 201);
    }

    public function login(): void
    {
        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $correo = isset($payload['correo']) && is_string($payload['correo']) ? $payload['correo'] : '';
        $contrasena = Request::raw('contrasena', $payload);

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

    public function verificarCorreo(): void
    {
        $payload = $this->readJsonBody();

        if ($payload === null) {
            $this->error(400, 'El cuerpo debe ser un objeto JSON válido');
            return;
        }

        $token = isset($payload['token']) && is_string($payload['token']) ? $payload['token'] : '';
        $resultado = $this->authService->verificarCorreo($token);

        if (!$resultado['ok']) {
            $this->error((int) $resultado['status'], (string) $resultado['message']);
            return;
        }

        $this->respond(['mensaje' => $resultado['message']], 200);
    }

    public function reenviarVerificacion(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $resultado = $this->authService->reenviarVerificacion((int) $usuario['usuario_id']);

        if (!$resultado['ok']) {
            $this->error((int) $resultado['status'], (string) $resultado['message']);
            return;
        }

        $this->respond([
            'mensaje' => $resultado['message'],
            'correo_enviado' => $resultado['correo_enviado'],
            'token_verificacion' => $resultado['token_verificacion'],
        ], 200);
    }

    public function logout(): void
    {
        Session::destroy();
        $this->flash('success', 'Sesión cerrada correctamente.');
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
            Request::raw('contrasena_actual', $payload),
            Request::raw('contrasena_nueva', $payload),
            Request::raw('contrasena_confirmacion', $payload)
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
