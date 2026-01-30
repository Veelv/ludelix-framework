<?php

namespace Ludelix\Interface\DI;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public function bind(string $abstract, mixed $concrete = null): void;
    public function singleton(string $abstract, mixed $concrete = null): void;
    public function instance(string $abstract, $instance): void;
    public function alias(string $alias, string $abstract): void;
    public function make(string $abstract, array $parameters = []): mixed;
    public function call(callable $callback, array $parameters = []): mixed;
    public function bound(string $abstract): bool;
    public function resolved(string $abstract): bool;
    public function flush(): void;
}