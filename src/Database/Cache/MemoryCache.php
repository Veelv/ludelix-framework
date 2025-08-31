<?php

namespace Ludelix\Database\Cache;

class MemoryCache
{
    protected array $cache = [];
    
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
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
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