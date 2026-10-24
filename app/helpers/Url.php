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
}
