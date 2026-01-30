<?php

namespace Ludelix\Validation\Support;

use Ludelix\Validation\Core\ValidationResult;

/**
 * ValidationCache - Cache system for validation results
 * 
 * Provides caching for validation results to improve performance
 */
class ValidationCache
{
    protected array $cache = [];
    protected int $maxSize = 1000;
    protected bool $enabled = true;

    /**
     * Get cached validation result
     */
    public function get(string $key): ?ValidationResult
    {
        if (!$this->enabled) {
            return null;
        }

        if (!isset($this->cache[$key])) {
            return null;
        }

        $cached = $this->cache[$key];
        
        // Check if cache is expired
        if (isset($cached['expires_at']) && time() > $cached['expires_at']) {
            unset($this->cache[$key]);
            return null;
        }

        return $cached['result'];
    }

    /**
     * Set validation result in cache
     */
    public function set(string $key, ValidationResult $result, int $ttl = 3600): void
    {
        if (!$this->enabled) {
            return;
        }

        // Clean cache if it's too large
        if (count($this->cache) >= $this->maxSize) {
            $this->clean();
        }

        $this->cache[$key] = [
            'result' => $result,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return isset($this->cache[$key]);
    }

    /**
     * Remove item from cache
     */
    public function forget(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clear all cache
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * Clean expired cache entries
     */
    public function clean(): void
    {
        $now = time();
        foreach ($this->cache as $key => $cached) {
            if (isset($cached['expires_at']) && $now > $cached['expires_at']) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * Enable/disable cache
     */
    public function enable(bool $enabled = true): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Check if cache is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set maximum cache size
     */
    public function setMaxSize(int $size): self
    {
        $this->maxSize = $size;
        return $this;
    }

    /**
     * Get maximum cache size
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * Get cache size
     */
    public function size(): int
    {
        return count($this->cache);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $expired = 0;
        $now = time();
        
        foreach ($this->cache as $cached) {
            if (isset($cached['expires_at']) && $now > $cached['expires_at']) {
                $expired++;
            }
        }

        return [
            'total_entries' => count($this->cache),
            'expired_entries' => $expired,
            'valid_entries' => count($this->cache) - $expired,
            'max_size' => $this->maxSize,
            'enabled' => $this->enabled,
        ];
    }

    /**
     * Get cache keys
     */
    public function keys(): array
    {
        return array_keys($this->cache);
    }

    /**
     * Get cache info for key
     */
    public function info(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $cached = $this->cache[$key];
        return [
            'created_at' => $cached['created_at'],
            'expires_at' => $cached['expires_at'] ?? null,
            'ttl' => ($cached['expires_at'] ?? 0) - time(),
            'is_expired' => isset($cached['expires_at']) && time() > $cached['expires_at'],
        ];
    }
} 