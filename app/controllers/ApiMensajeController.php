<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Helpers\Session;
use App\Services\MensajeService;

final class ApiMensajeController extends JsonController
{
    public function __construct(private readonly MensajeService $mensajes)
    {
    }

    public function index(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $this->responder($this->mensajes->listar((int) ($usuario['usuario_id'] ?? Session::usuarioId()), $_GET));
    }

    public function destinatarios(): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $this->responder($this->mensajes->destinatarios(
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) ($usuario['rol'] ?? '')
        ));
    }

    public function crear(): void
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

        $this->responder($this->mensajes->crear(
            (int) ($usuario['usuario_id'] ?? Session::usuarioId()),
            (string) ($usuario['rol'] ?? ''),
            $payload
        ));
    }

    public function marcarLeido(string $id): void
    {
        $usuario = $this->requireAuth();

        if ($usuario === null) {
            return;
        }

        $mensajeId = trim($id);

        if (!ctype_digit($mensajeId) || (int) $mensajeId < 1) {
            $this->error(400, 'El identificador del mensaje no es válido');
            return;
        }

        $this->responder($this->mensajes->marcarLeido(
            (int) $mensajeId,
            (int) ($usuario['usuario_id'] ?? Session::usuarioId())
        ));
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
