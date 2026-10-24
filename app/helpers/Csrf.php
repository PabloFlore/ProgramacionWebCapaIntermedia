<?php

declare(strict_types=1);

namespace App\Helpers;

final class Csrf
{
    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validate(mixed $token): bool
    {
        Session::start();
        $guardado = $_SESSION['csrf_token'] ?? null;

        return is_string($token) && is_string($guardado) && hash_equals($guardado, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
            . '">';
    }
}
