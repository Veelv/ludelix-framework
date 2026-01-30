<?php

namespace Ludelix\Routing\Cache;

use Ludelix\Cache\CacheManager;

/**
 * Route Cache - High-Performance Route Caching System
 * 
 * Specialized caching system for route resolution with intelligent
 * invalidation and performance optimization strategies.
 * 
 * @package Ludelix\Routing\Cache
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class RouteCache
{
    protected CacheManager $cache;
    protected array $config;
    protected string $prefix = 'route:';
    protected int $defaultTtl = 3600;

    public function __construct(CacheManager $cache = null, array $config = [])
    {
        $this->cache = $cache ?? new CacheManager();
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'route:';
        $this->defaultTtl = $config['ttl'] ?? 3600;
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($this->prefix . $key);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        return $this->cache->put($this->prefix . $key, $value, $ttl);
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->prefix . $key);
    }

    public function flush(): bool
    {
        return $this->cache->flush();
    }

    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }

    public function tags(array $tags): self
    {
        $this->cache->tags($tags);
        return $this;
    }

    public function getStats(): array
    {
        return [
            'prefix' => $this->prefix,
            'default_ttl' => $this->defaultTtl,
            'driver' => get_class($this->cache),
        ];
    }
}