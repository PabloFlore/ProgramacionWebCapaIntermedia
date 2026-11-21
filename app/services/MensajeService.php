<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contracts\CursoRepositoryInterface;
use App\Models\Contracts\MensajeRepositoryInterface;
use App\Models\Contracts\UsuarioRepositoryInterface;
use App\Services\Contracts\MensajeServiceInterface;

class MensajeService implements MensajeServiceInterface
{
    private const ROLES_DESTINO = [
        'estudiante' => ['instructor', 'admin'],
        'instructor' => ['estudiante'],
        'admin' => ['estudiante', 'instructor'],
    ];
    private const MAX_ASUNTO = 150;
    private const MAX_CUERPO = 4000;
    private const MAX_LIMIT = 50;

    public function __construct(
        private readonly MensajeRepositoryInterface $mensajes,
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly CursoRepositoryInterface $cursos
    ) {
    }

    public function listar(int $usuarioId, array $filtros): array
    {
        $buzon = isset($filtros['buzon']) && is_string($filtros['buzon'])
            ? $filtros['buzon']
            : 'recibidos';

        if (!in_array($buzon, ['recibidos', 'enviados'], true)) {
            return $this->fallo(400, 'buzon debe ser recibidos o enviados.');
        }

        $errores = [];
        $pagina = $this->enteroPositivo($filtros['page'] ?? null, 1, $errores, 'page');
        $limite = $this->enteroPositivo($filtros['limite'] ?? $filtros['limit'] ?? null, 10, $errores, 'limite');
        $limite = min($limite, self::MAX_LIMIT);

        if ($errores !== []) {
            return $this->fallo(400, 'Paginación no válida.', $errores);
        }

        $desplazamiento = ($pagina - 1) * $limite;
        $total = $this->mensajes->contarBandeja($usuarioId, $buzon);

        return [
            'ok' => true,
            'data' => $this->mensajes->listarBandeja($usuarioId, $buzon, $limite, $desplazamiento),
            'pagination' => [
                'page' => $pagina,
                'limite' => $limite,
                'buzon' => $buzon,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limite),
            ],
            'no_leidos' => $this->mensajes->contarNoLeidos($usuarioId),
        ];
    }

    public function destinatarios(int $usuarioId, string $rol): array
    {
        $roles = self::ROLES_DESTINO[$rol] ?? null;

        if ($roles === null) {
            return $this->fallo(403, 'Tu rol no puede enviar mensajes.');
        }

        return [
            'ok' => true,
            'data' => $this->mensajes->listarDestinatarios($roles, $usuarioId),
        ];
    }

    public function crear(int $remitenteId, string $rol, array $datos): array
    {
        $rolesPermitidos = self::ROLES_DESTINO[$rol] ?? null;

        if ($rolesPermitidos === null) {
            return $this->fallo(403, 'Tu rol no puede enviar mensajes.');
        }

        $errores = [];
        $destinatarioId = $this->enteroPositivo($datos['destinatario_id'] ?? null, 0, $errores, 'destinatario_id');

        $asunto = isset($datos['asunto']) && is_string($datos['asunto']) ? trim($datos['asunto']) : '';
        $cuerpo = isset($datos['cuerpo']) && is_string($datos['cuerpo']) ? trim($datos['cuerpo']) : '';

        if ($asunto === '' || strlen($asunto) > self::MAX_ASUNTO) {
            $errores[] = 'El asunto es obligatorio y no puede superar 150 caracteres.';
        }

        if ($cuerpo === '' || strlen($cuerpo) > self::MAX_CUERPO) {
            $errores[] = 'El cuerpo es obligatorio y no puede superar 4000 caracteres.';
        }

        $cursoId = null;
        if (isset($datos['curso_id']) && $datos['curso_id'] !== '' && $datos['curso_id'] !== null) {
            $cursoId = $this->enteroPositivo($datos['curso_id'], 0, $errores, 'curso_id');
        }

        if ($errores !== []) {
            return $this->fallo(400, 'No se pudo enviar el mensaje.', $errores);
        }

        if ($destinatarioId === $remitenteId) {
            return $this->fallo(400, 'No puedes enviarte un mensaje a ti mismo.');
        }

        $destinatario = $this->usuarios->buscarPorId($destinatarioId);

        if ($destinatario === null || (bool) $destinatario['bloqueado']) {
            return $this->fallo(404, 'Destinatario no disponible.');
        }

        if (!in_array($destinatario['rol'], $rolesPermitidos, true)) {
            return $this->fallo(403, 'No tienes permiso para escribir a ese usuario.');
        }

        if ($cursoId !== null && $this->cursos->buscarPorId($cursoId) === null) {
            return $this->fallo(404, 'El curso indicado no existe.');
        }

        $mensajeId = $this->mensajes->crear($remitenteId, $destinatarioId, $cursoId, $asunto, $cuerpo);

        return [
            'ok' => true,
            'status' => 201,
            'message' => 'Mensaje enviado correctamente.',
            'mensaje_id' => $mensajeId,
        ];
    }

    public function marcarLeido(int $mensajeId, int $usuarioId): array
    {
        $mensaje = $this->mensajes->buscarPorId($mensajeId);

        if ($mensaje === null) {
            return $this->fallo(404, 'Mensaje no encontrado.');
        }

        if ((int) $mensaje['destinatario_id'] !== $usuarioId) {
            return $this->fallo(403, 'No puedes modificar este mensaje.');
        }

        if (!$mensaje['leido']) {
            $this->mensajes->marcarLeido($mensajeId, $usuarioId);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Mensaje marcado como leído.',
        ];
    }

    private function enteroPositivo(
        mixed $valor,
        int $porOmision,
        array &$errores,
        string $nombre
    ): int {
        if ($valor === null || $valor === '') {
            return $porOmision;
        }

        if ((is_int($valor) || is_string($valor)) && ctype_digit((string) $valor) && (int) $valor > 0) {
            return (int) $valor;
        }

        $errores[] = $nombre . ' debe ser un entero positivo.';

        return $porOmision;
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
