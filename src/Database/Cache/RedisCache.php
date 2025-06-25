<?php

namespace Ludelix\Database\Cache;

class RedisCache
{
    protected $redis;
    
    public function __construct($redis = null)
    {
        $this->redis = $redis ?? new \Redis();
    }
    
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value ? unserialize($value) : null;
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->redis->setex($key, $ttl, serialize($value));
    }
    
    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
    
    public function clear(): void
    {
        $this->redis->flushAll();
    }
}