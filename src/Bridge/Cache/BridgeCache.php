<?php

namespace Ludelix\Bridge\Cache;

use DateInterval;
use DateTimeImmutable;

/**
 * Very lightweight in-memory cache used by the Bridge system.
 *
 * This is **not** intended to be a production-grade cache layer – it only
 * fulfils the minimal requirements for the framework to boot when no real
 * cache implementation was registered in the container.
 *
 * If the application binds another cache instance under the key
 * `bridge.cache`, that implementation will be preferred and this class will
 * never be instantiated.
 */
class BridgeCache
{
    /**
     * @var array<string, array{value:mixed,expires:?int}>
     */
    private array $items = [];

    /**
     * Retrieve an item from the cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    /**
     * Store an item in the cache.
     *
     * @param int|DateInterval|null $ttl Time-to-live in seconds or \DateInterval.
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $expires = null;
        if ($ttl instanceof DateInterval) {
            $expires = (new DateTimeImmutable())->add($ttl)->getTimestamp();
        } elseif (is_int($ttl)) {
            $expires = time() + $ttl;
        }

        $this->items[$key] = [
            'value'    => $value,
            'expires'  => $expires,
        ];

        return true;
    }

    /**
     * Check if the cache contains the given key and it has not expired.
     */
    public function has(string $key): bool
    {
        if (!isset($this->items[$key])) {
            return false;
        }

        $expires = $this->items[$key]['expires'];
        if ($expires !== null && $expires < time()) {
            unset($this->items[$key]);
            return false;
        }

        return true;
    }

    /**
     * Delete a cache entry.
     */
    public function delete(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    /**
     * Clear all cached items.
     */
    public function clear(): bool
    {
        $this->items = [];
        return true;
    }
}
