<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\Contracts\AccessMiddlewareFactoryInterface;

final class AccessMiddlewareFactory implements AccessMiddlewareFactoryInterface
{
    public function create(?array $roles): MiddlewareInterface
    {
        return new AccessMiddleware($roles);
    }
}
