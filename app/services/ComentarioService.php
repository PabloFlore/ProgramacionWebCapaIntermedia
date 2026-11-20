<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comentario;
use App\Models\Curso;

class ComentarioService
{
    public function __construct(
        private readonly Comentario $comentarios,
        private readonly Curso $cursos
    ) {
    }

    public function listarPorCurso(int $cursoId): array
    {
        if ($this->cursos->buscarPorId($cursoId) === null) {
            return $this->fallo(404, 'Curso no encontrado.');
        }

        return [
            'ok' => true,
            'data' => $this->comentarios->listarPorCurso($cursoId),
            'resumen' => $this->comentarios->resumenCurso($cursoId),
        ];
    }

    public function crear(int $cursoId, array $datos, int $estudianteId): array
    {
        if ($this->cursos->buscarPorId($cursoId) === null) {
            return $this->fallo(404, 'Curso no encontrado.');
        }

        $errores = [];
        $calificacion = $datos['calificacion'] ?? null;
        $calificacionValida = is_int($calificacion) || (is_string($calificacion) && ctype_digit($calificacion));
        $calificacion = $calificacionValida ? (int) $calificacion : 0;

        if ($calificacion < 1 || $calificacion > 10) {
            $errores[] = 'La calificación debe estar entre 1 y 10.';
        }

        $texto = isset($datos['comentario']) && is_string($datos['comentario'])
            ? trim($datos['comentario'])
            : '';

        if (strlen($texto) > 1000) {
            $errores[] = 'El comentario no puede superar 1000 caracteres.';
        }

        if ($errores !== []) {
            return $this->fallo(400, 'No se pudo publicar el comentario.', $errores);
        }

        if (!$this->comentarios->puedeComentar($cursoId, $estudianteId)) {
            return $this->fallo(403, 'Solo puedes comentar cursos que hayas completado.');
        }

        if ($this->comentarios->yaComento($cursoId, $estudianteId)) {
            return $this->fallo(409, 'Ya publicaste un comentario para este curso.');
        }

        $comentarioId = $this->comentarios->crear(
            $cursoId,
            $estudianteId,
            $calificacion,
            $texto === '' ? null : $texto
        );

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Comentario publicado correctamente.',
            'comentario_id' => $comentarioId,
            'resumen' => $this->comentarios->resumenCurso($cursoId),
        ];
    }

    public function listarModeracion(array $filtros): array
    {
        $errores = [];
        $cursoId = null;

        if (isset($filtros['curso_id']) && $filtros['curso_id'] !== '') {
            $valor = $filtros['curso_id'];

            if (!is_scalar($valor) || !ctype_digit((string) $valor) || (int) $valor < 1) {
                $errores[] = 'curso_id debe ser un entero positivo.';
            } else {
                $cursoId = (int) $valor;
            }
        }

        $incluirEliminados = false;
        if (isset($filtros['incluir_eliminados']) && $filtros['incluir_eliminados'] !== '') {
            $bandera = $filtros['incluir_eliminados'];

            if ($bandera === true || $bandera === 1 || $bandera === '1' || $bandera === 'true') {
                $incluirEliminados = true;
            } elseif ($bandera !== false && $bandera !== 0 && $bandera !== '0' && $bandera !== 'false') {
                $errores[] = 'incluir_eliminados debe ser 0 o 1.';
            }
        }

        if ($errores !== []) {
            return $this->fallo(400, 'Filtros de moderación no válidos.', $errores);
        }

        return [
            'ok' => true,
            'data' => $this->comentarios->listarModeracion($cursoId, $incluirEliminados),
        ];
    }

    public function eliminar(int $comentarioId, array $datos, int $adminId): array
    {
        $comentario = $this->comentarios->buscarPorId($comentarioId);

        if ($comentario === null) {
            return $this->fallo(404, 'Comentario no encontrado.');
        }

        if ((bool) $comentario['eliminado_por_admin']) {
            return $this->fallo(409, 'El comentario ya fue moderado.');
        }

        $causa = isset($datos['causa_eliminacion']) && is_string($datos['causa_eliminacion'])
            ? trim($datos['causa_eliminacion'])
            : '';

        if ($causa === '' || strlen($causa) > 500) {
            return $this->fallo(400, 'La causa de eliminación es obligatoria.', [
                'Indica un motivo de hasta 500 caracteres.',
            ]);
        }

        $this->comentarios->moderar($comentarioId, $causa, $adminId);

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Comentario moderado correctamente.',
        ];
    }

    private function fallo(int $status, string $message, array $detalles = []): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'detalles' => $detalles !== [] ? $detalles : [$message],
        ];
    }
}
