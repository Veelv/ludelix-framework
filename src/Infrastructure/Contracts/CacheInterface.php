<?php

declare(strict_types=1);

namespace Ludelix\Infrastructure\Contracts;

/**
 * Cache Interface for Infrastructure
 * 
 * Defines the contract for cache implementations used by infrastructure components.
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
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

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
     * Get the time to live for a cache key in seconds
     *
     * @param string $key
     * @return int|null
     */
    public function getTimeToLive(string $key): ?int;

    /**
     * Clear all items from the cache
     *
     * @return bool
     */
    public function flush(): bool;
}
