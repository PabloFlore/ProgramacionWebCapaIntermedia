<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\Contracts\CorsMiddlewareFactoryInterface;

final class CorsMiddlewareFactory implements CorsMiddlewareFactoryInterface
{
    public function create(string $method): MiddlewareInterface
    {
        return new CorsMiddleware($method);
    }
}
