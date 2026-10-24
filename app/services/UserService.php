<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Upload;
use App\Models\Usuario;

final class UserService
{
    private const GENEROS = ['femenino', 'masculino', 'prefiero_no_decir'];

    public function perfil(int $usuarioId): ?array
    {
        return (new Usuario())->buscarPorId($usuarioId);
    }

    public function listar(?string $rol = null, ?bool $bloqueado = null): array
    {
        return (new Usuario())->listar($rol, $bloqueado);
    }

    public function actualizarPerfil(int $usuarioId, array $datos, ?array $archivo = null): array
    {
        $modelo = new Usuario();
        $usuario = $modelo->buscarPorId($usuarioId);

        if ($usuario === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Usuario no encontrado'];
        }

        $errores = [];

        if (array_key_exists('nombre', $datos)) {
            if (!is_string($datos['nombre'])) {
                $nombre = '';
                $errores[] = 'nombre es obligatorio';
            } else {
                $nombre = trim($datos['nombre']);
            }
        } else {
            $nombre = (string) $usuario['nombre'];
        }

        if ($nombre === '' && !in_array('nombre es obligatorio', $errores, true)) {
            $errores[] = 'nombre es obligatorio';
        } elseif (mb_strlen($nombre) > 100) {
            $errores[] = 'nombre no debe exceder 100 caracteres';
        }

        $genero = $usuario['genero'];
        if (array_key_exists('genero', $datos)) {
            if ($datos['genero'] !== null && !is_string($datos['genero'])) {
                $errores[] = 'genero no es válido';
            } else {
                $genero = $datos['genero'] === null ? null : trim($datos['genero']);
                $genero = $genero === '' ? null : $genero;

                if ($genero !== null && !in_array($genero, self::GENEROS, true)) {
                    $errores[] = 'genero no es válido';
                }
            }
        }

        $fechaNacimiento = $usuario['fecha_nacimiento'];
        if (array_key_exists('fecha_nacimiento', $datos)) {
            if ($datos['fecha_nacimiento'] !== null && !is_string($datos['fecha_nacimiento'])) {
                $errores[] = 'fecha_nacimiento debe tener el formato AAAA-MM-DD';
            } else {
                $fechaNacimiento = $datos['fecha_nacimiento'] === null
                    ? null
                    : trim($datos['fecha_nacimiento']);
                $fechaNacimiento = $fechaNacimiento === '' ? null : $fechaNacimiento;

                if ($fechaNacimiento !== null) {
                    $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaNacimiento);

                    if ($fecha === false || $fecha->format('Y-m-d') !== $fechaNacimiento) {
                        $errores[] = 'fecha_nacimiento debe tener el formato AAAA-MM-DD';
                    } elseif ($fecha > new \DateTimeImmutable('today')) {
                        $errores[] = 'fecha_nacimiento no puede estar en el futuro';
                    }
                }
            }
        }

        $fotoPerfil = $usuario['foto_perfil'];
        if ($archivo !== null) {
            $subida = Upload::avatar($archivo);

            if (!$subida['ok']) {
                $errores[] = $subida['error'];
            } elseif ($subida['path'] !== null) {
                $fotoPerfil = $subida['path'];
            }
        }

        if ($errores !== []) {
            if (isset($subida['path']) && $subida['path'] !== null) {
                Upload::borrar($subida['path']);
            }

            return ['ok' => false, 'status' => 400, 'message' => $errores[0], 'detalles' => $errores];
        }

        $actualizado = $modelo->actualizarPerfil($usuarioId, [
            'nombre' => $nombre,
            'genero' => $genero,
            'fecha_nacimiento' => $fechaNacimiento,
            'foto_perfil' => $fotoPerfil,
        ]);

        if (!$actualizado) {
            if (isset($subida['path']) && $subida['path'] !== null) {
                Upload::borrar($subida['path']);
            }

            return ['ok' => false, 'status' => 500, 'message' => 'No se pudo actualizar el perfil'];
        }

        if ($fotoPerfil !== $usuario['foto_perfil']) {
            Upload::borrar($usuario['foto_perfil']);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Perfil actualizado correctamente',
            'usuario' => $modelo->buscarPorId($usuarioId),
        ];
    }

    public function actualizarFoto(int $usuarioId, array $archivo): array
    {
        return $this->actualizarPerfil($usuarioId, [], $archivo);
    }

    public function bloquear(int $adminId, int $usuarioId, ?string $motivo): array
    {
        if ($adminId === $usuarioId) {
            return ['ok' => false, 'status' => 400, 'message' => 'No puedes bloquear tu propia cuenta'];
        }

        $modelo = new Usuario();
        $usuario = $modelo->buscarPorId($usuarioId);

        if ($usuario === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Usuario no encontrado'];
        }

        if ($usuario['rol'] === 'admin'
            && (int) $usuario['bloqueado'] === 0
            && $modelo->contarAdminsActivos($usuarioId) === 0
        ) {
            return [
                'ok' => false,
                'status' => 400,
                'message' => 'No puedes bloquear al último administrador activo',
            ];
        }

        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            $motivo = 'Bloqueado por administrador';
        }

        $modelo->bloquear($usuarioId, $motivo, $adminId);

        return ['ok' => true, 'status' => 200, 'message' => 'Usuario bloqueado correctamente'];
    }

    public function reactivar(int $usuarioId): array
    {
        $modelo = new Usuario();
        $usuario = $modelo->buscarPorId($usuarioId);

        if ($usuario === null) {
            return ['ok' => false, 'status' => 404, 'message' => 'Usuario no encontrado'];
        }

        $modelo->reactivar($usuarioId);

        return ['ok' => true, 'status' => 200, 'message' => 'Usuario reactivado correctamente'];
    }
}
