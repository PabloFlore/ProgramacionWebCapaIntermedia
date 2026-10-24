<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Config\\' => __DIR__ . '/config/',
        'App\\Models\\' => __DIR__ . '/models/',
        'App\\Controllers\\' => __DIR__ . '/controllers/',
        'App\\Helpers\\' => __DIR__ . '/helpers/',
        'App\\Core\\' => __DIR__ . '/core/',
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $directory . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }

        return;
    }
});
