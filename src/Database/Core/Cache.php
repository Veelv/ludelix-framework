<?php

namespace Ludelix\Database\Core;

/**
 * Basic in-memory cache wrapper.
 */
class Cache
{
    /** @var array Cache storage */
    protected array $cache = [];

    /** @var int Default Time To Live in seconds */
    protected int $ttl = 3600;

    /**
     * Retrieves a value from the cache.
     *
     * @param string $key
     * @return mixed
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
     * Sets a value in the cache.
     *
     * @param string   $key
     * @param mixed    $value
     * @param int|null $ttl
     */
    public function set(string $key, mixed $value, int $ttl = null): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->ttl)
        ];
    }

    /**
     * Deletes a value from the cache.
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clears the entire cache.
     */
    public function clear(): void
    {
        $this->cache = [];
    }
}