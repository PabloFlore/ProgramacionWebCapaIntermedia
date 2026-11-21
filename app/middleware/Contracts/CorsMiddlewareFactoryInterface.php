<?php

declare(strict_types=1);

namespace App\Middleware\Contracts;

use App\Middleware\MiddlewareInterface;

interface CorsMiddlewareFactoryInterface
{
    public function create(string $method): MiddlewareInterface;
}
