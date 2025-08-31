<?php

namespace Ludelix\Database\Cache;

class FileCache
{
    protected string $path;
    
    public function __construct(string $path = '/tmp/cache')
    {
        $this->path = $path;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
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
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $file = $this->path . '/' . md5($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, serialize($data));
    }
    
    public function delete(string $key): void
    {
        $file = $this->path . '/' . md5($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
}