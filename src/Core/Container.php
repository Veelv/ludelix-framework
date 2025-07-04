<?php

namespace Ludelix\Core;

use Ludelix\Interface\DI\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionParameter;
use Closure;

class Container implements ContainerInterface
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $resolved = [];

    public function bind(string $abstract, mixed $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => false
        ];
    }

    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete ?? $abstract,
            'shared' => true
        ];
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->getConcrete($abstract);

        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        return $object;
    }

    public function call(callable $callback, array $parameters = []): mixed
    {
        return $callback(...$this->resolveMethodDependencies($callback, $parameters));
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function resolved(string $abstract): bool
    {
        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolved = [];
    }

    protected function getConcrete(string $abstract): mixed
    {
        return $this->bindings[$abstract]['concrete'] ?? $abstract;
    }

    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    protected function isShared(string $abstract): bool
    {
        return $this->bindings[$abstract]['shared'] ?? false;
    }

    protected function build(mixed $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new class("Target class [$concrete] does not exist.", 0, $e) extends \Exception implements NotFoundExceptionInterface {};
        }

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Target [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $parameters)) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            $result = $this->resolveDependency($dependency);
            $results[] = $result;
        }

        return $results;
    }

    protected function resolveDependency(ReflectionParameter $parameter): mixed
    {
        if ($parameter->getType() && !$parameter->getType()->isBuiltin()) {
            return $this->make($parameter->getType()->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new \Exception("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
    }

    protected function resolveMethodDependencies(callable $callback, array $parameters): array
    {
        $reflector = new \ReflectionFunction($callback);
        return $this->resolveDependencies($reflector->getParameters(), $parameters);
    }
}