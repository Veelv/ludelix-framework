<?php

namespace Ludelix\Ludou\Cache;

/**
 * Redis Template Cache
 * 
 * Redis-based cache for compiled templates
 */
class RedisCache
{
    protected $redis;
    protected string $prefix;
    protected int $ttl;

    public function __construct($redis = null, string $prefix = 'ludelix:template:', int $ttl = 3600)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    public function get(string $key): ?string
    {
        if (!$this->redis) {
            return null;
        }

        $value = $this->redis->get($this->prefix . $key);
        return $value !== false ? $value : null;
    }

    public function put(string $key, string $content): void
    {
        if (!$this->redis) {
            return;
        }

        $this->redis->setex($this->prefix . $key, $this->ttl, $content);
    }

    public function has(string $key): bool
    {
        if (!$this->redis) {
            return false;
        }

        return $this->redis->exists($this->prefix . $key) > 0;
    }

    public function forget(string $key): void
    {
        if (!$this->redis) {
            return;
        }

        $this->redis->del($this->prefix . $key);
    }

    public function flush(): void
    {
        if (!$this->redis) {
            return;
        }

        $keys = $this->redis->keys($this->prefix . '*');
        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    }

    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function isConnected(): bool
    {
        if (!$this->redis) {
            return false;
        }

        try {
            $this->redis->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
