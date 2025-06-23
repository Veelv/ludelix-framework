<?php

namespace Ludelix\Interface\Config;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function all(): array;
    public function forget(string $key): void;
    public function load(string $path): void;
}