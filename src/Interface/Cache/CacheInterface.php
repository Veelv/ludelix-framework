<?php

namespace Ludelix\Interface\Cache;

/**
 * Cache Interface
 * 
 * Defines the contract for cache implementations
 */
interface CacheInterface
{
    /**
     * Get an item from the cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function put(string $key, mixed $value, int $ttl = null): bool;

    /**
     * Check if an item exists in the cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool;

    /**
     * Clear all items from the cache
     *
     * @return bool
     */
    public function flush(): bool;
}