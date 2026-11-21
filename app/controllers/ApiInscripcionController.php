<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Services\Contracts\InscripcionServiceInterface;

final class ApiInscripcionController extends JsonController
{
    public function __construct(private readonly InscripcionServiceInterface $inscripciones)
    {
    }

    public function metodosPago(): void
    {
        $this->respond(['data' => $this->inscripciones->metodosPago()]);
    }

    public function misCursos(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $this->respond(['data' => $this->inscripciones->misInscripciones((int) $usuario['usuario_id'])]);
    }

    public function comprar(): void
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

        $cursoId = $this->id($payload['curso_id'] ?? null);
        $metodoPagoId = $this->id($payload['metodo_pago_id'] ?? null);
        $nivelId = array_key_exists('nivel_id', $payload) && $payload['nivel_id'] !== null && $payload['nivel_id'] !== ''
            ? $this->id($payload['nivel_id'])
            : null;

        if ($cursoId === null || $metodoPagoId === null || (array_key_exists('nivel_id', $payload) && $nivelId === null && $payload['nivel_id'] !== null && $payload['nivel_id'] !== '')) {
            $this->error(400, 'curso_id y metodo_pago_id son obligatorios; nivel_id debe ser válido si se envía');
            return;
        }

        $this->responder($this->inscripciones->comprar(
            (int) $usuario['usuario_id'],
            $cursoId,
            $metodoPagoId,
            $nivelId
        ));
    }

    public function confirmarPago(string $pagoId): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $id = $this->id($pagoId);

        if ($id === null) {
            $this->error(400, 'El identificador del pago no es válido');
            return;
        }

        $this->responder($this->inscripciones->confirmarPago($id, (int) $usuario['usuario_id']));
    }

    public function completarNivel(string $inscripcionId, string $nivelId): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $inscripcion = $this->id($inscripcionId);
        $nivel = $this->id($nivelId);

        if ($inscripcion === null || $nivel === null) {
            $this->error(400, 'Los identificadores no son válidos');
            return;
        }

        $this->responder($this->inscripciones->completarNivel(
            (int) $usuario['usuario_id'],
            $inscripcion,
            $nivel
        ));
    }

    public function diploma(string $inscripcionId): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $id = $this->id($inscripcionId);

        if ($id === null) {
            $this->error(400, 'El identificador de la inscripción no es válido');
            return;
        }

        $diploma = $this->inscripciones->diplomaPorInscripcion((int) $usuario['usuario_id'], $id);

        if ($diploma === null) {
            $this->error(404, 'El certificado todavía no está disponible');
            return;
        }

        $this->respond(['data' => $diploma]);
    }

    public function verificarDiploma(): void
    {
        $codigo = isset($_GET['codigo']) && is_string($_GET['codigo']) ? trim($_GET['codigo']) : '';

        if ($codigo === '') {
            $this->error(400, 'Ingresa un código de verificación');
            return;
        }

        if (mb_strlen($codigo) > 64 || preg_match('/^[A-Za-z0-9-]+$/', $codigo) !== 1) {
            $this->error(400, 'El código de verificación no es válido');
            return;
        }

        $diploma = $this->inscripciones->verificarDiploma($codigo);

        if ($diploma === null) {
            $this->error(404, 'No se encontró un certificado con ese código');
            return;
        }

        $this->respond(['data' => $diploma]);
    }

    private function id(mixed $valor): ?int
    {
        if (is_int($valor) && $valor > 0) {
            return $valor;
        }

        if (is_string($valor) && ctype_digit(trim($valor)) && (int) trim($valor) > 0) {
            return (int) trim($valor);
        }

        return null;
    }

    private function responder(array $resultado): void
    {
        $status = (int) ($resultado['status'] ?? 200);

        if (($resultado['ok'] ?? false) !== true) {
            $this->error($status, (string) ($resultado['message'] ?? 'No se pudo completar la solicitud'), [
                'detalles' => $resultado['detalles'] ?? [],
            ]);
            return;
        }

        unset($resultado['ok'], $resultado['status'], $resultado['detalles']);
        $this->respond($resultado, $status);
    }
}
