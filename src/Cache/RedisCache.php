<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Redis Cache
 * 
 * Redis-based cache with clustering support
 */
class RedisCache implements CacheInterface
{
    protected $redis;
    protected string $prefix;
    protected int $defaultTtl;

    public function __construct(array $config = [])
    {
        $this->prefix = $config['prefix'] ?? 'ludelix:';
        $this->defaultTtl = $config['ttl'] ?? 3600;

        if (!class_exists('Redis')) {
            throw new \RuntimeException(
                'Redis extension is not installed. Install it with: pecl install redis'
            );
        }

        if (isset($config['connection'])) {
            $this->redis = $config['connection'];
        } else {
            $this->redis = new \Redis();
            $this->redis->connect(
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 6379
            );

            if (isset($config['password'])) {
                $this->redis->auth($config['password']);
            }

            if (isset($config['database'])) {
                $this->redis->select($config['database']);
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->prefix . $key);

        if ($value === false) {
            return $default;
        }

        return unserialize($value);
    }

    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;

        return $this->redis->setex(
            $this->prefix . $key,
            $ttl,
            serialize($value)
        );
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($this->prefix . $key) > 0;
    }

    public function forget(string $key): bool
    {
        return $this->redis->del($this->prefix . $key) > 0;
    }

    public function flush(): bool
    {
        $keys = $this->redis->keys($this->prefix . '*');

        if (empty($keys)) {
            return true;
        }

        return $this->redis->del($keys) > 0;
    }

    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($this->prefix . $key, $value);
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrBy($this->prefix . $key, $value);
    }

    public function lock(string $key, int $ttl = 10): bool
    {
        return $this->redis->set(
            $this->prefix . 'lock:' . $key,
            1,
            ['nx', 'ex' => $ttl]
        );
    }

    public function unlock(string $key): bool
    {
        return $this->redis->del($this->prefix . 'lock:' . $key) > 0;
    }
}