<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use InvalidArgumentException;

final class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $abstract, callable|object|string $concrete, bool $shared = false): void
    {
        $this->bindings[ltrim($abstract, '\\')] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];

        unset($this->instances[ltrim($abstract, '\\')]);
    }

    public function singleton(string $abstract, callable|object|string $concrete): void
    {
        $this->set($abstract, $concrete, true);
    }

    public function get(string $abstract): object
    {
        $abstract = ltrim($abstract, '\\');

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            if (!class_exists($abstract)) {
                throw new InvalidArgumentException('No se puede resolver ' . $abstract);
            }

            $this->bindings[$abstract] = ['concrete' => $abstract, 'shared' => false];
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];

        if (is_callable($concrete) && !is_string($concrete)) {
            $object = $concrete($this);
        } elseif (is_object($concrete)) {
            $object = $concrete;
        } else {
            $object = $this->build((string) $concrete);
        }

        if ($binding['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function build(string $class): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(
                'No se puede resolver el parámetro $' . $parameter->getName() . ' de ' . $class
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
