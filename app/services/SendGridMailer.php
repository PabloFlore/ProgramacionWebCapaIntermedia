<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\SendGridHelper;
use App\Services\Contracts\MailerInterface;

final class SendGridMailer implements MailerInterface
{
    public function configurado(): bool
    {
        return SendGridHelper::configurado();
    }

    public function enviarVerificacion(string $correo, string $nombre, string $url): bool
    {
        return SendGridHelper::enviarVerificacion($correo, $nombre, $url);
    }
}
