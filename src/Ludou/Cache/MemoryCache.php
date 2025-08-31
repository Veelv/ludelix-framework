<?php

namespace Ludelix\Ludou\Cache;

/**
 * Memory Template Cache
 * 
 * In-memory cache for compiled templates
 */
class MemoryCache
{
    protected array $cache = [];
    protected int $maxSize;
    protected int $currentSize = 0;

    public function __construct(int $maxSize = 1000)
    {
        $this->maxSize = $maxSize;
    }

    public function get(string $key): ?string
    {
        return $this->cache[$key] ?? null;
    }

    public function put(string $key, string $content): void
    {
        if ($this->currentSize >= $this->maxSize) {
            $this->evictOldest();
        }

        if (!isset($this->cache[$key])) {
            $this->currentSize++;
        }

        $this->cache[$key] = $content;
    }

    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    public function forget(string $key): void
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->currentSize--;
        }
    }

    public function flush(): void
    {
        $this->cache = [];
        $this->currentSize = 0;
    }

    public function size(): int
    {
        return $this->currentSize;
    }

    public function getStats(): array
    {
        return [
            'size' => $this->currentSize,
            'max_size' => $this->maxSize,
            'usage' => $this->currentSize / $this->maxSize * 100,
            'keys' => array_keys($this->cache)
        ];
    }

    protected function evictOldest(): void
    {
        if (!empty($this->cache)) {
            $firstKey = array_key_first($this->cache);
            $this->forget($firstKey);
        }
    }
}
