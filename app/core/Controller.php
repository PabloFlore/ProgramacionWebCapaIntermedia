<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function model(string $name)
    {
        $class = 'App\\Models\\' . $name;

        return new $class();
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (is_file($viewPath)) {
            require_once $viewPath;
            return;
        }

        http_response_code(404);
        echo 'Vista no encontrada.';
    }
}
