<?php

namespace Ludelix\Cache;

use Ludelix\Interface\Cache\CacheInterface;

/**
 * Tenant Aware Cache
 * 
 * Cache with automatic tenant isolation
 */
class TenantAwareCache implements CacheInterface
{
    protected CacheInterface $cache;
    protected string $tenantId;

    public function __construct(CacheInterface $cache, string $tenantId = null)
    {
        $this->cache = $cache;
        $this->tenantId = $tenantId ?? $this->getCurrentTenant();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($this->tenantKey($key), $default);
    }

    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        return $this->cache->put($this->tenantKey($key), $value, $ttl);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->tenantKey($key));
    }

    public function forget(string $key): bool
    {
        return $this->cache->forget($this->tenantKey($key));
    }

    public function flush(): bool
    {
        // Only flush current tenant's cache
        return $this->flushTenant($this->tenantId);
    }

    public function flushTenant(string $tenantId): bool
    {
        // Implementation depends on cache driver
        if (method_exists($this->cache, 'deleteByPattern')) {
            return $this->cache->deleteByPattern("tenant:{$tenantId}:*");
        }
        
        // Fallback: use tags if available
        if ($this->cache instanceof TaggedCache) {
            return $this->cache->flushTag("tenant:{$tenantId}");
        }
        
        return false;
    }

    public function switchTenant(string $tenantId): self
    {
        return new self($this->cache, $tenantId);
    }

    protected function tenantKey(string $key): string
    {
        return "tenant:{$this->tenantId}:{$key}";
    }

    protected function getCurrentTenant(): string
    {
        if (function_exists('tenant')) {
            return tenant()->id ?? 'default';
        }
        
        return 'default';
    }
}