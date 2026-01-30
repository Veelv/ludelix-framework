<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Memory Cache
 * 
 * In-memory cache storage using PHP arrays.
 * Data persists only for the duration of the current request.
 * Useful for testing and temporary caching within a single request lifecycle.
 */
class MemoryCache implements CacheInterface
{
    /**
     * Cache storage array
     *
     * @var array
     */
    protected array $storage = [];

    /**
     * Default TTL in seconds
     *
     * @var int
     */
    protected int $defaultTtl;

    /**
     * Create a new memory cache instance
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->defaultTtl = $config['ttl'] ?? 3600;
    }

    /**
     * Retrieve an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        $item = $this->storage[$key];

        // Check if expired
        if ($item['expires'] < time()) {
            $this->forget($key);
            return $default;
        }

        return $item['value'];
    }

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;

        $this->storage[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return true;
    }

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * Remove an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    /**
     * Remove all items from the cache
     *
     * @return bool
     */
    public function flush(): bool
    {
        $this->storage = [];
        return true;
    }

    /**
     * Get all cached items (for debugging)
     *
     * @return array
     */
    public function all(): array
    {
        return $this->storage;
    }

    /**
     * Get the number of items in cache
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->storage);
    }
}
