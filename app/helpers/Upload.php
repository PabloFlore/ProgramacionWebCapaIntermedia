<?php

declare(strict_types=1);

namespace App\Helpers;

final class Upload
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function avatar(array $archivo): array
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null];
        }

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'No se pudo subir la imagen.'];
        }

        if (($archivo['size'] ?? 0) <= 0 || ($archivo['size'] ?? 0) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'La imagen debe pesar como máximo 2 MB.'];
        }

        $tmp = $archivo['tmp_name'] ?? '';

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'El archivo recibido no es válido.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $extension = is_string($mime) ? (self::TIPOS[$mime] ?? null) : null;

        if ($extension === null) {
            return ['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o WebP.'];
        }

        $directorio = dirname(__DIR__, 2) . '/public/uploads/avatars';

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            return ['ok' => false, 'error' => 'No se pudo preparar el directorio de imágenes.'];
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($tmp, $directorio . '/' . $nombre)) {
            return ['ok' => false, 'error' => 'No se pudo guardar la imagen.'];
        }

        return ['ok' => true, 'path' => 'uploads/avatars/' . $nombre];
    }

    public static function borrar(?string $path): void
    {
        if ($path === null || !preg_match('#^uploads/avatars/[A-Za-z0-9._-]+$#', $path)) {
            return;
        }

        $archivo = dirname(__DIR__, 2) . '/public/' . $path;

        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}
