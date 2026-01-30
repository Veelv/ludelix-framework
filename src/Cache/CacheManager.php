<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Cache Manager
 * 
 * Central cache management with multiple drivers and tagging support
 */
class CacheManager implements CacheInterface
{
    protected array $drivers = [];
    protected string $defaultDriver;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'file';
    }

    public function driver(string $name = null): CacheInterface
    {
        $name = $name ?: $this->defaultDriver;
        
        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }
        
        return $this->drivers[$name];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver()->get($key, $default);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        return $this->driver()->put($key, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return $this->driver()->forget($key);
    }

    public function flush(): bool
    {
        return $this->driver()->flush();
    }

    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }

    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }

    public function tags(array $tags): TaggedCache
    {
        return new TaggedCache($this->driver(), $tags);
    }

    protected function createDriver(string $name): CacheInterface
    {
        $config = $this->config['drivers'][$name] ?? [];
        
        return match($name) {
            'file' => new FileCache($config),
            'memory' => new MemoryCache($config),
            'redis' => new RedisCache($config),
            'database' => new DatabaseCache($config),
            default => throw new \InvalidArgumentException("Cache driver [$name] not supported")
        };
    }
}