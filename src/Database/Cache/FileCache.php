<?php

namespace Ludelix\Database\Cache;

/**
 * File-based cache implementation.
 *
 * Stores cached data as serialized files in the filesystem.
 * Suitable for persistent caching across requests.
 */
class FileCache
{
    /**
     * @var string The directory path where cache files are stored.
     */
    protected string $path;

    /**
     * @param string $path The directory path (default: '/tmp/cache').
     */
    public function __construct(string $path = '/tmp/cache')
    {
        $this->path = $path;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Retrieve an item from the cache.
     *
     * @param string $key The cache key.
     * @return mixed The cached value, or null if not found/expired.
     */
    public function get(string $key): mixed
    {
        $file = $this->path . '/' . md5($key);
        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
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
        $file = $this->path . '/' . md5($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, serialize($data));
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key The cache key to remove.
     */
    public function delete(string $key): void
    {
        $file = $this->path . '/' . md5($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}