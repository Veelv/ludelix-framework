<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * File-based Cache
 * 
 * Stores data in filesystem cache with TTL support
 */
class FileCache implements CacheInterface
{
    protected bool $enabled;
    protected string $cachePath;
    protected int $defaultTtl;

    public function __construct(array $config = [])
    {
        $this->enabled = $config['enabled'] ?? true;
        $this->cachePath = $config['path'] ?? sys_get_temp_dir() . '/ludelix_cache';
        $this->defaultTtl = $config['ttl'] ?? 3600;
        
        if ($this->enabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($this->getPath($key)));
        
        if ($data['expires'] < time()) {
            $this->forget($key);
            return $default;
        }
        
        return $data['value'];
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check if value contains closures and skip caching if it does
        if ($this->containsClosures($value)) {
            return false;
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($this->getPath($key), serialize($data), LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->enabled && file_exists($this->getPath($key));
    }

    public function forget(string $key): bool
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return true;
    }

    public function flush(): bool
    {
        if (!$this->enabled || !is_dir($this->cachePath)) {
            return true;
        }
        
        $files = glob($this->cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    protected function getPath(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }

    protected function containsClosures(mixed $value): bool
    {
        if (is_callable($value) && !is_string($value)) {
            return true;
        }
        
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsClosures($item)) {
                    return true;
                }
            }
        }
        
        if (is_object($value)) {
            $reflection = new \ReflectionObject($value);
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $propertyValue = $property->getValue($value);
                if ($this->containsClosures($propertyValue)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}