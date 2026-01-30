<?php

namespace Ludelix\Database\Cache;

/**
 * In-memory cache implementation.
 *
 * Stores data in a local array for the duration of the request.
 * Useful for testing or short-lived caching within a single process.
 */
class MemoryCache
{
    /**
     * @var array Internal storage for cached items.
     */
    protected array $cache = [];

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if not found/expired.
     */
    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $item = $this->cache[$key];
        if ($item['expires'] < time()) {
            unset($this->cache[$key]);
            return null;
        }

        return $item['value'];
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key   The cache key.
     * @param mixed  $value The value to store.
     * @param int    $ttl   Time To Live in seconds (default: 3600).
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key The cache key to remove.
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clear all items from the cache.
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}