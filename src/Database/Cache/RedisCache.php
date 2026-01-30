<?php

namespace Ludelix\Database\Cache;

/**
 * Redis-based cache implementation.
 *
 * Uses Redis key-value store for high-performance caching.
 * Requires the phpredis extension or similar client.
 */
class RedisCache
{
    /**
     * @var object The Redis client instance.
     */
    protected $redis;

    /**
     * @param object|null $redis Optional Redis instance. If null, a new \Redis instance is created.
     */
    public function __construct($redis = null)
    {
        $this->redis = $redis ?? new \Redis();
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if not found.
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
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
        $this->redis->setex($key, $ttl, serialize($value));
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key The cache key to remove.
     */
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    /**
     * Clear all items from the cache.
     */
    public function clear(): void
    {
        $this->redis->flushAll();
    }
}