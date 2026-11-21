<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\JsonController;
use App\Services\Contracts\KardexServiceInterface;

final class ApiKardexController extends JsonController
{
    public function __construct(private readonly KardexServiceInterface $kardex)
    {
    }

    public function index(): void
    {
        $usuario = $this->requireRole(['estudiante']);

        if ($usuario === null) {
            return;
        }
        $this->respond([
            'data' => $this->kardex->obtener((int) ($usuario['usuario_id'] ?? 0), $_GET),
        ]);
    }
}
