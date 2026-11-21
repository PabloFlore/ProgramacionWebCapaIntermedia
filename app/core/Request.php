<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function string(string $key, mixed $source = null, string $default = ''): string
    {
        $values = $source ?? $_REQUEST;
        $value = $values[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public static function raw(string $key, mixed $source = null, string $default = ''): string
    {
        $values = $source ?? $_REQUEST;
        $value = $values[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public static function int(string $key, mixed $source = null, ?int $default = null): ?int
    {
        $value = self::string($key, $source, '');

        if ($value === '' || !ctype_digit($value)) {
            return $default;
        }

        return (int) $value;
    }

    public static function bool(string $key, mixed $source = null, bool $default = false): bool
    {
        $value = self::string($key, $source, '');

        if ($value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
            return trim($_SERVER[$key]);
        }

        if (strtolower($name) === 'content-type'
            && isset($_SERVER['CONTENT_TYPE'])
            && is_string($_SERVER['CONTENT_TYPE'])
        ) {
            return trim($_SERVER['CONTENT_TYPE']);
        }

        return '';
    }

    public static function json(): array
    {
        $content = file_get_contents('php://input');

        if ($content === false || $content === '') {
            return [];
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }
}
