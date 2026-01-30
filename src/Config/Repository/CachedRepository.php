<?php

namespace Ludelix\Config\Repository;

use Ludelix\Interface\Config\ConfigInterface;

class CachedRepository implements ConfigInterface
{
    protected ConfigInterface $config;
    protected array $cache = [];

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->config->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
        $this->config->set($key, $value);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache) || $this->config->has($key);
    }

    public function all(): array
    {
        return $this->config->all();
    }

    public function forget(string $key): void
    {
        unset($this->cache[$key]);
        $this->config->forget($key);
    }

    public function load(string $path): void
    {
        $this->config->load($path);
        $this->cache = [];
    }
}