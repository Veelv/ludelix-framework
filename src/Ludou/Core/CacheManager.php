<?php

namespace Ludelix\Ludou\Core;

/**
 * Template Cache Manager
 * 
 * Manages template compilation caching
 */
class CacheManager
{
    protected string $cacheDir;
    protected bool $enabled;

    public function __construct(bool $enabled = true, string $cacheDir = null)
    {
        $this->enabled = $enabled;
        $this->cacheDir = $cacheDir ?: sys_get_temp_dir() . '/ludelix_templates';
        
        if ($this->enabled && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $path = $this->getCachePath($key);
        return file_exists($path) ? file_get_contents($path) : null;
    }

    public function put(string $key, string $content): void
    {
        if (!$this->enabled) {
            return;
        }

        file_put_contents($this->getCachePath($key), $content, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->enabled && file_exists($this->getCachePath($key));
    }

    public function forget(string $key): void
    {
        $path = $this->getCachePath($key);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function flush(): void
    {
        if (!$this->enabled || !is_dir($this->cacheDir)) {
            return;
        }

        $files = glob($this->cacheDir . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function needsRecompilation(string $templatePath, string $cacheKey): bool
    {
        if (!$this->has($cacheKey)) {
            return true;
        }

        $cachePath = $this->getCachePath($cacheKey);
        return filemtime($templatePath) > filemtime($cachePath);
    }

    protected function getCachePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.php';
    }
}
