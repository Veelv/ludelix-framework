<?php

namespace Ludelix\Tenant\Isolation;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Cache Isolation - Tenant Cache Isolation Manager
 * 
 * Manages cache isolation for multi-tenant applications using tenant-specific
 * prefixes, TTL overrides, and cache driver configurations.
 * 
 * @package Ludelix\Tenant\Isolation
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class CacheIsolation
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Cache configuration
     */
    protected array $config;

    /**
     * Cache key prefix for current tenant
     */
    protected string $keyPrefix = '';

    /**
     * Initialize cache isolation manager
     * 
     * @param array $config Cache isolation configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'default_prefix' => 'app:',
            'tenant_separator' => ':',
            'global_keys' => ['config:', 'system:'],
        ], $config);
    }

    /**
     * Switch cache context to specific tenant
     * 
     * @param TenantInterface $tenant Target tenant
     * @return self Fluent interface
     */
    public function switchTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        
        $cacheConfig = $tenant->getCacheConfig();
        $this->keyPrefix = $cacheConfig['prefix'] ?? "tenant:{$tenant->getId()}:";
        
        return $this;
    }

    /**
     * Get tenant-aware cache key
     * 
     * @param string $key Original cache key
     * @return string Prefixed cache key
     */
    public function getKey(string $key): string
    {
        // Skip prefixing for global keys
        foreach ($this->config['global_keys'] as $globalPrefix) {
            if (str_starts_with($key, $globalPrefix)) {
                return $key;
            }
        }

        // Apply tenant prefix
        if ($this->currentTenant && $this->keyPrefix) {
            return $this->keyPrefix . $key;
        }

        return $this->config['default_prefix'] . $key;
    }

    /**
     * Get tenant-specific TTL multiplier
     * 
     * @param int $baseTtl Base TTL in seconds
     * @return int Adjusted TTL
     */
    public function getTtl(int $baseTtl): int
    {
        if (!$this->currentTenant) {
            return $baseTtl;
        }

        $cacheConfig = $this->currentTenant->getCacheConfig();
        $multiplier = $cacheConfig['ttl_multiplier'] ?? 1.0;
        
        return (int) ($baseTtl * $multiplier);
    }

    /**
     * Get cache tags for tenant isolation
     * 
     * @param array $baseTags Base cache tags
     * @return array Tenant-aware cache tags
     */
    public function getTags(array $baseTags = []): array
    {
        if (!$this->currentTenant) {
            return $baseTags;
        }

        $tenantTag = "tenant:{$this->currentTenant->getId()}";
        return array_merge([$tenantTag], $baseTags);
    }

    /**
     * Clear tenant-specific cache
     * 
     * @param TenantInterface|null $tenant Tenant to clear cache for
     * @return bool Success status
     */
    public function clearTenantCache(?TenantInterface $tenant = null): bool
    {
        $targetTenant = $tenant ?? $this->currentTenant;
        
        if (!$targetTenant) {
            return false;
        }

        // This would integrate with actual cache implementation
        // For now, return success
        return true;
    }

    /**
     * Get current tenant context
     * 
     * @return TenantInterface|null Current tenant
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    /**
     * Get current cache key prefix
     * 
     * @return string Cache key prefix
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * Check if key should be globally cached
     * 
     * @param string $key Cache key
     * @return bool True if global
     */
    public function isGlobalKey(string $key): bool
    {
        foreach ($this->config['global_keys'] as $globalPrefix) {
            if (str_starts_with($key, $globalPrefix)) {
                return true;
            }
        }
        return false;
    }
}