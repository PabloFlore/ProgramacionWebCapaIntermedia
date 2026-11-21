<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\CursoRepositoryInterface;
use App\Models\Contracts\DiplomaRepositoryInterface;
use App\Models\Contracts\InscripcionRepositoryInterface;
use App\Models\Contracts\MetodoPagoRepositoryInterface;
use App\Models\Contracts\NivelRepositoryInterface;
use App\Models\Contracts\OrdenRepositoryInterface;
use App\Models\Contracts\PagoRepositoryInterface;
use App\Models\Contracts\ProgresoNivelRepositoryInterface;
use App\Services\Contracts\DiplomaServiceInterface;
use App\Services\Contracts\InscripcionServiceInterface;
use App\Services\Contracts\PagoServiceInterface;
use PDO;
use Throwable;

final class InscripcionService implements InscripcionServiceInterface
{
    public function __construct(
        private readonly InscripcionRepositoryInterface $inscripciones,
        private readonly CursoRepositoryInterface $cursos,
        private readonly NivelRepositoryInterface $niveles,
        private readonly MetodoPagoRepositoryInterface $metodosPago,
        private readonly OrdenRepositoryInterface $ordenes,
        private readonly PagoRepositoryInterface $pagos,
        private readonly ProgresoNivelRepositoryInterface $progreso,
        private readonly PagoServiceInterface $pagoService,
        private readonly DiplomaServiceInterface $diplomaService,
        private readonly PDO $db
    ) {
    }

    public function metodosPago(): array
    {
        return $this->metodosPago->listarActivos();
    }

    public function misInscripciones(int $estudianteId): array
    {
        return $this->inscripciones->listarPorEstudiante($estudianteId);
    }

    public function comprar(int $estudianteId, int $cursoId, int $metodoPagoId, ?int $nivelId = null): array
    {
        $curso = $this->cursos->buscarPorId($cursoId);

        if ($curso === null || $curso['estatus'] !== 'activo') {
            return $this->fallo(404, 'El curso no está disponible');
        }

        $metodo = $this->metodosPago->buscarPorId($metodoPagoId);

        if ($metodo === null) {
            return $this->fallo(400, 'El método de pago no es válido');
        }

        $nivel = null;

        if ($nivelId !== null) {
            $nivel = $this->niveles->buscarPorId($nivelId);

            if ($nivel === null || (int) $nivel['curso_id'] !== $cursoId) {
                return $this->fallo(400, 'El nivel seleccionado no pertenece al curso');
            }
        }

        $existente = $this->inscripciones->buscar($estudianteId, $cursoId);

        if ($existente !== null) {
            if ($existente['estado_pago'] === 'pagado') {
                return $this->fallo(409, 'Ya estás inscrito en este curso');
            }

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Ya tienes una compra pendiente de pago',
                'inscripcion_id' => (int) $existente['inscripcion_id'],
                'pendiente' => true,
            ];
        }

        $monto = $nivel !== null && $nivel['precio'] !== null
            ? (float) $nivel['precio']
            : (float) $curso['precio'];
        $referencia = 'ORD-' . strtoupper(bin2hex(random_bytes(6)));

        $this->db->beginTransaction();

        try {
            $ordenId = $this->ordenes->crear([
                'estudiante_id' => $estudianteId,
                'curso_id' => $cursoId,
                'monto' => $monto,
                'referencia' => $referencia,
            ]);

            if ($nivel !== null) {
                $this->ordenes->agregarDetalle($ordenId, (int) $nivel['nivel_id'], 'Nivel: ' . $nivel['titulo'], $monto);
            } else {
                $this->ordenes->agregarDetalle($ordenId, null, 'Curso: ' . $curso['titulo'], $monto);
            }

            $pagoId = $this->pagos->crear([
                'orden_id' => $ordenId,
                'metodo_pago_id' => $metodoPagoId,
                'monto' => $monto,
                'referencia' => $referencia,
            ]);

            $inscripcionId = $this->inscripciones->crear([
                'estudiante_id' => $estudianteId,
                'curso_id' => $cursoId,
                'monto_pagado' => $monto,
                'estado_pago' => 'pendiente',
                'referencia_pago' => $referencia,
                'metodo_pago_id' => $metodoPagoId,
                'orden_id' => $ordenId,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            error_log('Error al comprar curso: ' . $exception->getMessage());

            return $this->fallo(500, 'No se pudo iniciar la compra');
        }

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Orden de compra creada. Confirma el pago para activar tu inscripción.',
            'inscripcion_id' => $inscripcionId,
            'orden_id' => $ordenId,
            'pago_id' => $pagoId,
            'monto' => $monto,
            'referencia' => $referencia,
        ];
    }

    public function confirmarPago(int $pagoId, int $estudianteId): array
    {
        $pago = $this->pagoService->buscar($pagoId);

        if ($pago === null) {
            return $this->fallo(404, 'Pago no encontrado');
        }

        if ((int) $pago['estudiante_id'] !== $estudianteId) {
            return $this->fallo(403, 'Este pago no pertenece a tu cuenta');
        }

        if ($pago['orden_estatus'] === 'cancelada') {
            return $this->fallo(409, 'La orden fue cancelada');
        }

        $inscripcion = $this->inscripciones->buscar($estudianteId, (int) $pago['curso_id']);

        if ($inscripcion === null) {
            return $this->fallo(404, 'Inscripción no encontrada');
        }

        $this->db->beginTransaction();

        try {
            if ($pago['estatus'] !== 'aprobado') {
                $this->pagoService->aprobar($pago, (string) $pago['referencia']);
            }

            $this->inscripciones->marcarPagada(
                (int) $inscripcion['inscripcion_id'],
                (float) $pago['monto'],
                (string) $pago['referencia'],
                (int) $pago['metodo_pago_id'],
                (int) $pago['orden_id']
            );

            foreach ($this->ordenes->listarDetalles((int) $pago['orden_id']) as $detalle) {
                if ($detalle['nivel_id'] !== null) {
                    $this->progreso->asegurar((int) $inscripcion['inscripcion_id'], (int) $detalle['nivel_id']);
                }
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            error_log('Error al confirmar pago: ' . $exception->getMessage());

            return $this->fallo(500, 'No se pudo confirmar el pago');
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Pago confirmado. Ya puedes acceder al curso.',
            'inscripcion_id' => (int) $inscripcion['inscripcion_id'],
        ];
    }

    public function completarNivel(int $estudianteId, int $inscripcionId, int $nivelId): array
    {
        $inscripcion = $this->inscripciones->buscarPorId($inscripcionId);

        if ($inscripcion === null || (int) $inscripcion['estudiante_id'] !== $estudianteId) {
            return $this->fallo(404, 'Inscripción no encontrada');
        }

        if ($inscripcion['estado_pago'] !== 'pagado') {
            return $this->fallo(403, 'Debes completar el pago antes de avanzar');
        }

        $nivel = $this->niveles->buscarPorId($nivelId);

        if ($nivel === null || (int) $nivel['curso_id'] !== (int) $inscripcion['curso_id']) {
            return $this->fallo(400, 'El nivel no pertenece a este curso');
        }

        $this->db->beginTransaction();

        try {
            $this->progreso->asegurar($inscripcionId, $nivelId);
            $this->progreso->marcarCompletado($inscripcionId, $nivelId);

            $total = count($this->niveles->listarPorCurso((int) $inscripcion['curso_id']));
            $completados = $this->progreso->contarCompletados($inscripcionId);
            $porcentaje = $total > 0 ? (int) round($completados * 100 / $total) : 0;
            $completado = $total > 0 && $completados >= $total;
            $this->inscripciones->actualizarProgreso($inscripcionId, $porcentaje, $completado);

            $diploma = $completado ? $this->diplomaService->emitir($inscripcionId) : null;
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            error_log('Error al completar nivel: ' . $exception->getMessage());

            return $this->fallo(500, 'No se pudo registrar el avance');
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => $completado
                ? 'Curso completado. Tu certificado ya está disponible.'
                : 'Avance registrado correctamente.',
            'progreso' => $porcentaje,
            'completado' => $completado,
            'diploma' => $diploma,
        ];
    }

    public function verificarDiploma(string $codigo): ?array
    {
        return $this->diplomaService->verificar($codigo);
    }

    public function diplomaPorInscripcion(int $estudianteId, int $inscripcionId): ?array
    {
        $inscripcion = $this->inscripciones->buscarPorId($inscripcionId);

        if ($inscripcion === null || (int) $inscripcion['estudiante_id'] !== $estudianteId) {
            return null;
        }

        return $this->diplomaService->porInscripcion($inscripcionId);
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
