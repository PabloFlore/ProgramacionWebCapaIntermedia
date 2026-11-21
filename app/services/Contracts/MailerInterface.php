<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface MailerInterface
{
    public function configurado(): bool;

    public function enviarVerificacion(string $correo, string $nombre, string $url): bool;
}
