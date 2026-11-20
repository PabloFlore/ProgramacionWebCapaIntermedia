<?php

declare(strict_types=1);

namespace App\Helpers;

final class SendGridHelper
{
    public static function configurado(): bool
    {
        $config = self::config();

        return $config['key'] !== '' && $config['from'] !== '';
    }

    public static function enviarVerificacion(string $correo, string $nombre, string $url): bool
    {
        $config = self::config();

        if ($config['key'] === '' || $config['from'] === '') {
            error_log('SendGrid no configurado: no se envió el correo de verificación.');
            return false;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            error_log('SendGrid: correo de destino no válido.');
            return false;
        }

        $nombreLimpio = trim(preg_replace('/[\r\n]+/', ' ', $nombre) ?? $nombre);
        $nombreSeguro = htmlspecialchars($nombreLimpio, ENT_QUOTES, 'UTF-8');
        $urlSegura = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $texto = "Hola {$nombreLimpio},\n\nConfirma tu correo para terminar de configurar tu cuenta de SOSCUR:\n{$url}\n\n"
            . "Si no creaste esta cuenta, ignora este mensaje.\n";

        $payload = [
            'personalizations' => [[
                'to' => [['email' => $correo, 'name' => $nombreLimpio]],
                'subject' => 'Verifica tu correo - SOSCUR Portal',
            ]],
            'from' => ['email' => $config['from'], 'name' => 'SOSCUR Portal'],
            'content' => [
                ['type' => 'text/plain', 'value' => $texto],
                ['type' => 'text/html', 'value' => '<p>Hola ' . $nombreSeguro . ',</p>'
                    . '<p>Confirma tu correo para terminar de configurar tu cuenta de SOSCUR.</p>'
                    . '<p><a href="' . $urlSegura . '">Verificar correo</a></p>'
                    . '<p>Si no creaste esta cuenta, ignora este mensaje.</p>'],
            ],
        ];

        if ($config['sandbox']) {
            $payload['mail_settings'] = ['sandbox_mode' => ['enable' => true]];
        }

        $cuerpo = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($cuerpo === false) {
            error_log('SendGrid: no se pudo serializar el correo de verificación.');
            return false;
        }

        $curl = curl_init($config['url']);

        if ($curl === false) {
            error_log('SendGrid: no se pudo inicializar cURL.');
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $config['key'],
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $respuesta = curl_exec($curl);
        $estatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($respuesta === false || $estatus !== 202) {
            error_log('SendGrid: fallo el envío (HTTP ' . $estatus . '). ' . $error
                . ' Respuesta: ' . substr((string) $respuesta, 0, 500));
            return false;
        }

        return true;
    }

    private static function config(): array
    {
        $config = require dirname(__DIR__) . '/config/config.php';

        return $config['sendgrid'];
    }
}
