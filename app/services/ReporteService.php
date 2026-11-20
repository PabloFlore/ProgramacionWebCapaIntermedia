<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Reporte;

class ReporteService
{
    private const ROLES = ['admin', 'instructor', 'estudiante'];
    private const ESTADOS_PAGO = ['pendiente', 'pagado', 'reembolsado'];

    public function __construct(private Reporte $reportes)
    {
    }

    public function generar(array $filtrosCrudos, int $usuarioId, string $rol): array
    {
        $errores = [];
        $filtros = $this->normalizar($filtrosCrudos, $errores);

        if ($errores !== []) {
            return [
                'ok' => false,
                'status' => 400,
                'message' => 'Filtros de reporte no válidos.',
                'detalles' => $errores,
            ];
        }

        $instructorId = $rol === 'admin' ? null : $usuarioId;

        $usuarios = $this->reportes->resumenUsuarios($filtros, $instructorId);
        $ventas = $this->reportes->resumenVentas($filtros, $instructorId);
        $totalDetalle = $this->reportes->contarDetalle($filtros, $instructorId);
        $totalPaginas = max(1, (int) ceil($totalDetalle / $filtros['limite']));

        return [
            'ok' => true,
            'reporte' => [
                'filtros' => $filtros,
                'resumen' => [
                    'usuarios_totales' => $usuarios['total'],
                    'usuarios_activos' => $usuarios['activos'],
                    'usuarios_bloqueados' => $usuarios['bloqueados'],
                    'cursos' => $this->reportes->contarCursos($filtros, $instructorId),
                    'inscripciones' => $ventas['inscripciones'],
                    'ventas_pagadas' => $ventas['ventas_pagadas'],
                    'ingresos' => $ventas['ingresos'],
                ],
                'por_rol' => $this->reportes->usuariosPorRol($filtros, $instructorId),
                'por_curso' => $this->reportes->ventasPorCurso($filtros, $instructorId),
                'detalle' => $this->reportes->detalleVentas(
                    $filtros,
                    $instructorId,
                    $filtros['limite'],
                    ($filtros['pagina'] - 1) * $filtros['limite']
                ),
                'paginacion' => [
                    'pagina' => $filtros['pagina'],
                    'limite' => $filtros['limite'],
                    'total' => $totalDetalle,
                    'total_paginas' => $totalPaginas,
                ],
            ],
        ];
    }

    private function normalizar(array $filtros, array &$errores): array
    {
        $desde = $this->fecha($filtros['desde'] ?? null, 'desde', $errores);
        $hasta = $this->fecha($filtros['hasta'] ?? null, 'hasta', $errores);

        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            $errores[] = 'desde no puede ser mayor que hasta.';
        }

        $rol = $this->enum($filtros['rol'] ?? null, 'rol', self::ROLES, $errores);
        $estadoPago = $this->enum($filtros['estado_pago'] ?? null, 'estado_pago', self::ESTADOS_PAGO, $errores);
        $bloqueado = $this->booleano($filtros['bloqueado'] ?? null, $errores);
        $limiteCrudo = $filtros['limite'] ?? $filtros['limit'] ?? 10;

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'curso_id' => $this->enteroPositivo($filtros['curso_id'] ?? null, 'curso_id', $errores),
            'categoria_id' => $this->enteroPositivo($filtros['categoria_id'] ?? null, 'categoria_id', $errores),
            'estado_pago' => $estadoPago,
            'rol' => $rol,
            'bloqueado' => $bloqueado,
            'pagina' => $this->enteroPositivo($filtros['pagina'] ?? 1, 'pagina', $errores) ?? 1,
            'limite' => min($this->enteroPositivo($limiteCrudo, 'limite', $errores) ?? 10, 100),
        ];
    }

    private function fecha(mixed $valor, string $campo, array &$errores): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            $errores[] = $campo . ' debe tener formato YYYY-MM-DD.';

            return null;
        }

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            $errores[] = $campo . ' no es una fecha válida.';

            return null;
        }

        return $valor;
    }

    private function enteroPositivo(mixed $valor, string $campo, array &$errores): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_scalar($valor) || !ctype_digit((string) $valor) || (int) $valor < 1) {
            $errores[] = $campo . ' debe ser un entero positivo.';

            return null;
        }

        return (int) $valor;
    }

    private function enum(mixed $valor, string $campo, array $permitidos, array &$errores): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_string($valor) || !in_array($valor, $permitidos, true)) {
            $errores[] = $campo . ' no es válido.';

            return null;
        }

        return $valor;
    }

    private function booleano(mixed $valor, array &$errores): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor === true || $valor === 1 || $valor === '1' || $valor === 'true') {
            return 1;
        }

        if ($valor === false || $valor === 0 || $valor === '0' || $valor === 'false') {
            return 0;
        }

        $errores[] = 'bloqueado debe ser 0 o 1.';

        return null;
    }
}
