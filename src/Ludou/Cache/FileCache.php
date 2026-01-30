<?php

namespace Ludelix\Ludou\Cache;

/**
 * File-based Template Cache
 * 
 * Stores compiled templates in filesystem cache
 */
class FileCache
{
    protected bool $enabled;
    protected string $cachePath;

    public function __construct(bool $enabled = true, string $cachePath = null)
    {
        $this->enabled = $enabled;
        $this->cachePath = $cachePath ?: sys_get_temp_dir() . '/ludelix_templates';
        
        if ($this->enabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function has(string $key): bool
    {
        return $this->enabled && file_exists($this->getPath($key));
    }

    public function get(string $key): ?string
    {
        if (!$this->has($key)) {
            return null;
        }
        return file_get_contents($this->getPath($key));
    }

    public function put(string $key, string $content): void
    {
        if ($this->enabled) {
            file_put_contents($this->getPath($key), $content, LOCK_EX);
        }
    }

    public function forget(string $key): void
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function flush(): void
    {
        if (!$this->enabled || !is_dir($this->cachePath)) {
            return;
        }
        
        $files = glob($this->cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    protected function getPath(string $key): string
    {
        return $this->cachePath . '/' . $key . '.php';
    }
}
