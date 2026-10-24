<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\Usuario;
use PDOException;
use Throwable;

final class AuthService
{
    public const MAX_INTENTOS = 3;
    private const ROLES_REGISTRO = ['estudiante', 'instructor'];
    private const GENEROS = ['femenino', 'masculino', 'prefiero_no_decir'];

    public function registro(array $datos): array
    {
        $errores = [];

        $nombreDato = $datos['nombre'] ?? null;
        $correoDato = $datos['correo'] ?? null;
        $contrasenaDato = $datos['contrasena'] ?? null;
        $confirmacionDato = $datos['contrasena_confirmacion'] ?? null;
        $rolDato = $datos['rol'] ?? 'estudiante';

        $nombre = is_string($nombreDato) ? trim($nombreDato) : '';
        $correo = is_string($correoDato) ? strtolower(trim($correoDato)) : '';
        $contrasena = is_string($contrasenaDato) ? $contrasenaDato : '';
        $confirmacion = is_string($confirmacionDato) ? $confirmacionDato : '';
        $rol = is_string($rolDato) ? $rolDato : '';

        if (!is_string($nombreDato) || $nombre === '') {
            $errores[] = 'nombre es obligatorio';
        } elseif (mb_strlen($nombre) > 100) {
            $errores[] = 'nombre no debe exceder 100 caracteres';
        }

        if (!is_string($correoDato) || $correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'correo no es válido';
        }

        if (!in_array($rol, self::ROLES_REGISTRO, true)) {
            $errores[] = 'rol debe ser estudiante o instructor';
        }

        $genero = null;
        if (array_key_exists('genero', $datos) && $datos['genero'] !== null) {
            if (!is_string($datos['genero'])) {
                $errores[] = 'genero no es válido';
            } elseif (trim($datos['genero']) !== '') {
                $genero = trim($datos['genero']);
                if (!in_array($genero, self::GENEROS, true)) {
                    $errores[] = 'genero no es válido';
                }
            }
        }

        $fechaNacimiento = null;
        if (array_key_exists('fecha_nacimiento', $datos) && $datos['fecha_nacimiento'] !== null) {
            if (!is_string($datos['fecha_nacimiento'])) {
                $errores[] = 'fecha_nacimiento debe tener el formato AAAA-MM-DD';
            } elseif (trim($datos['fecha_nacimiento']) !== '') {
                $fechaNacimiento = trim($datos['fecha_nacimiento']);
                $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaNacimiento);

                if ($fecha === false || $fecha->format('Y-m-d') !== $fechaNacimiento) {
                    $errores[] = 'fecha_nacimiento debe tener el formato AAAA-MM-DD';
                } elseif ($fecha > new \DateTimeImmutable('today')) {
                    $errores[] = 'fecha_nacimiento no puede estar en el futuro';
                }
            }
        }

        $fotoPerfil = null;
        if (array_key_exists('foto_perfil', $datos) && $datos['foto_perfil'] !== null) {
            if (!is_string($datos['foto_perfil'])) {
                $errores[] = 'foto_perfil no es válida';
            } elseif (trim($datos['foto_perfil']) !== '') {
                $fotoPerfil = trim($datos['foto_perfil']);

                if (!preg_match('#^uploads/avatars/[A-Za-z0-9._-]+$#', $fotoPerfil)) {
                    $errores[] = 'foto_perfil no es válida';
                }
            }
        }

        $errores = array_merge($errores, $this->validarContrasena($contrasena));

        if ($contrasena !== $confirmacion) {
            $errores[] = 'Las contraseñas no coinciden';
        }

        if ($errores !== []) {
            return ['ok' => false, 'status' => 400, 'message' => $errores[0], 'detalles' => $errores];
        }

        $modelo = new Usuario();

        if ($modelo->existeCorreo($correo)) {
            return ['ok' => false, 'status' => 409, 'message' => 'El correo ya está registrado'];
        }

        try {
            $id = $modelo->crear([
                'nombre' => $nombre,
                'correo' => $correo,
                'contrasena_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
                'rol' => $rol,
                'genero' => $genero,
                'fecha_nacimiento' => $fechaNacimiento,
                'foto_perfil' => $fotoPerfil,
                'correo_verificado' => 0,
            ]);
        } catch (PDOException $exception) {
            if ($this->esCorreoDuplicado($exception)) {
                return ['ok' => false, 'status' => 409, 'message' => 'El correo ya está registrado'];
            }

            error_log('Error al registrar usuario: ' . $exception->getMessage());

            return ['ok' => false, 'status' => 500, 'message' => 'No se pudo crear la cuenta'];
        }

        $usuario = $modelo->buscarPorId($id);

        if ($usuario === null) {
            return ['ok' => false, 'status' => 500, 'message' => 'No se pudo recuperar la cuenta creada'];
        }

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Cuenta creada correctamente',
            'usuario' => $usuario,
        ];
    }

    public function login(string $correo, string $contrasena): array
    {
        $correo = strtolower(trim($correo));

        if ($correo === '' || $contrasena === '') {
            return [
                'ok' => false,
                'status' => 400,
                'message' => 'correo y contrasena son obligatorios',
            ];
        }

        $db = Database::getConnection();
        $modelo = new Usuario();

        try {
            $db->beginTransaction();
            $usuario = $modelo->buscarPorCorreoParaActualizar($correo);

            if ($usuario === null) {
                $db->commit();

                return ['ok' => false, 'status' => 401, 'message' => 'Credenciales inválidas'];
            }

            if ((int) $usuario['bloqueado'] === 1) {
                $db->commit();

                return [
                    'ok' => false,
                    'status' => 401,
                    'message' => 'Cuenta bloqueada. Contacta al administrador.',
                ];
            }

            if (!password_verify($contrasena, $usuario['contrasena_hash'])) {
                $intentos = (int) $usuario['intentos_fallidos'] + 1;
                $bloquear = $intentos >= self::MAX_INTENTOS;

                $modelo->actualizarIntentos(
                    (int) $usuario['usuario_id'],
                    $intentos,
                    $bloquear ? 'Bloqueo automático por 3 intentos fallidos' : null
                );

                $db->commit();

                return [
                    'ok' => false,
                    'status' => 401,
                    'message' => $bloquear
                        ? 'Cuenta bloqueada por demasiados intentos fallidos.'
                        : 'Credenciales inválidas',
                ];
            }

            if ((int) $usuario['intentos_fallidos'] !== 0) {
                $modelo->reiniciarIntentos((int) $usuario['usuario_id']);
            }

            if (password_needs_rehash($usuario['contrasena_hash'], PASSWORD_DEFAULT)) {
                $modelo->actualizarContrasena(
                    (int) $usuario['usuario_id'],
                    password_hash($contrasena, PASSWORD_DEFAULT)
                );
            }

            $db->commit();

            unset($usuario['contrasena_hash']);

            return ['ok' => true, 'status' => 200, 'usuario' => $usuario];
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Error al iniciar sesión: ' . $exception->getMessage());

            return ['ok' => false, 'status' => 500, 'message' => 'No se pudo iniciar sesión'];
        }
    }

    public function cambiarContrasena(
        int $usuarioId,
        string $contrasenaActual,
        string $contrasenaNueva,
        string $confirmacion
    ): array {
        $modelo = new Usuario();
        $usuario = $modelo->buscarPorIdConHash($usuarioId);

        if ($usuario === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Usuario no encontrado'];
        }

        if (!password_verify($contrasenaActual, $usuario['contrasena_hash'])) {
            return ['ok' => false, 'status' => 401, 'message' => 'La contraseña actual es incorrecta'];
        }

        $errores = $this->validarContrasena($contrasenaNueva);

        if ($contrasenaNueva !== $confirmacion) {
            $errores[] = 'Las contraseñas no coinciden';
        }

        if ($errores !== []) {
            return ['ok' => false, 'status' => 400, 'message' => $errores[0], 'detalles' => $errores];
        }

        $modelo->actualizarContrasena($usuarioId, password_hash($contrasenaNueva, PASSWORD_DEFAULT));

        return ['ok' => true, 'status' => 200, 'message' => 'Contraseña actualizada correctamente'];
    }

    private function validarContrasena(string $contrasena): array
    {
        $errores = [];

        if (mb_strlen($contrasena) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        if (!preg_match('/[a-z]/', $contrasena)) {
            $errores[] = 'La contraseña debe incluir una minúscula';
        }
        if (!preg_match('/[A-Z]/', $contrasena)) {
            $errores[] = 'La contraseña debe incluir una mayúscula';
        }
        if (!preg_match('/\d/', $contrasena)) {
            $errores[] = 'La contraseña debe incluir un número';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $contrasena)) {
            $errores[] = 'La contraseña debe incluir un carácter especial';
        }

        return $errores;
    }

    private function esCorreoDuplicado(PDOException $exception): bool
    {
        return $exception->getCode() === '23000'
            && isset($exception->errorInfo[1])
            && (int) $exception->errorInfo[1] === 1062;
    }
}
