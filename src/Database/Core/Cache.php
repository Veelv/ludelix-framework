<?php

namespace Ludelix\Database\Core;

class Cache
{
    protected array $cache = [];
    protected int $ttl = 3600;
    
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
    
    public function set(string $key, mixed $value, int $ttl = null): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + ($ttl ?? $this->ttl)
        ];
    }
    
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }
    
    public function clear(): void
    {
        $this->cache = [];
    }
}