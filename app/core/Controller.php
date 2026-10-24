<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Session;
use App\Helpers\Url;

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

    protected function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    protected function flash(string $tipo, string $mensaje): void
    {
        Session::start();
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    protected function takeFlash(): ?array
    {
        Session::start();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        return is_array($flash) ? $flash : null;
    }
}
