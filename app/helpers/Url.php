<?php

declare(strict_types=1);

namespace App\Helpers;

final class Url
{
    public static function base(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', dirname(dirname($scriptName)));
        $base = rtrim($base, '/');

        return $base === '' || $base === '.' || $base === '/' ? '' : $base;
    }

    public static function to(string $path = ''): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::to($path);
    }

    public static function absolute(string $path = ''): string
    {
        $config = require dirname(__DIR__) . '/config/config.php';
        $base = trim((string) ($config['app_url'] ?? ''));

        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
            $host = preg_replace('/[^A-Za-z0-9.\-:]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?? 'localhost';
            $base = ($https ? 'https' : 'http') . '://' . $host . self::base();
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}
