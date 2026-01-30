<?php

namespace Ludelix\Tenant\Resolution\Strategies;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\PRT\Request;

/**
 * Path Strategy - Path-based Tenant Resolution
 * 
 * Resolves tenants based on URL path segments (e.g., /tenant/dashboard, /admin/tenant).
 * Supports configurable path patterns and tenant identification rules.
 * 
 * @package Ludelix\Tenant\Resolution\Strategies
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class PathStrategy
{
    /**
     * Path segment position for tenant ID
     */
    protected int $tenantSegmentPosition = 1;

    /**
     * Path prefixes to check
     */
    protected array $pathPrefixes = [
        '/tenant/',
        '/t/',
        '/client/',
    ];

    /**
     * Resolution cache
     */
    protected array $cache = [];

    /**
     * Initialize strategy with configuration
     * 
     * @param array $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->tenantSegmentPosition = $config['segment_position'] ?? 1;
        
        if (isset($config['prefixes'])) {
            $this->pathPrefixes = $config['prefixes'];
        }
    }

    /**
     * Resolve tenant from URL path segments
     * 
     * @param Request $request HTTP request to analyze
     * @param array $options Resolution options
     * @return TenantInterface|null Resolved tenant or null
     */
    public function resolve(Request $request, array $options = []): ?TenantInterface
    {
        $path = $request->getPath();
        
        if (!$path || $path === '/') {
            return null;
        }

        // Check cache first
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        // Try prefix-based resolution
        $tenant = $this->resolveFromPrefix($path);
        
        // Try segment-based resolution if prefix fails
        if (!$tenant) {
            $tenant = $this->resolveFromSegment($path);
        }

        // Cache result
        if ($tenant) {
            $this->cache[$path] = $tenant;
        }

        return $tenant;
    }

    /**
     * Resolve tenant from path prefix
     * 
     * @param string $path Request path
     * @return TenantInterface|null Resolved tenant
     */
    protected function resolveFromPrefix(string $path): ?TenantInterface
    {
        foreach ($this->pathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $remaining = substr($path, strlen($prefix));
                $segments = explode('/', trim($remaining, '/'));
                
                if (!empty($segments[0]) && $this->isValidTenantId($segments[0])) {
                    return $this->createTenantFromPath($segments[0], $path);
                }
            }
        }

        return null;
    }

    /**
     * Resolve tenant from path segment
     * 
     * @param string $path Request path
     * @return TenantInterface|null Resolved tenant
     */
    protected function resolveFromSegment(string $path): ?TenantInterface
    {
        $segments = explode('/', trim($path, '/'));
        
        if (isset($segments[$this->tenantSegmentPosition])) {
            $tenantId = $segments[$this->tenantSegmentPosition];
            
            if ($this->isValidTenantId($tenantId)) {
                return $this->createTenantFromPath($tenantId, $path);
            }
        }

        return null;
    }

    /**
     * Validate tenant ID format
     * 
     * @param string $tenantId Tenant identifier
     * @return bool True if valid
     */
    protected function isValidTenantId(string $tenantId): bool
    {
        return preg_match('/^[a-zA-Z0-9\-_]+$/', $tenantId) && strlen($tenantId) <= 64;
    }

    /**
     * Create tenant instance from path
     * 
     * @param string $tenantId Tenant identifier
     * @param string $path Request path
     * @return TenantInterface Tenant instance
     */
    protected function createTenantFromPath(string $tenantId, string $path): TenantInterface
    {
        return new Tenant([
            'id' => $tenantId,
            'name' => ucfirst(str_replace(['-', '_'], ' ', $tenantId)),
            'status' => 'active',
            'metadata' => [
                'resolved_from' => 'path',
                'path' => $path
            ],
            'database' => [
                'strategy' => 'prefix',
                'prefix' => $tenantId . '_'
            ],
            'cache' => [
                'prefix' => "tenant:{$tenantId}:"
            ]
        ]);
    }

    /**
     * Set tenant segment position
     * 
     * @param int $position Segment position (0-based)
     * @return self Fluent interface
     */
    public function setSegmentPosition(int $position): self
    {
        $this->tenantSegmentPosition = $position;
        return $this;
    }

    /**
     * Add path prefix for tenant resolution
     * 
     * @param string $prefix Path prefix
     * @return self Fluent interface
     */
    public function addPrefix(string $prefix): self
    {
        $this->pathPrefixes[] = $prefix;
        return $this;
    }

    /**
     * Clear resolution cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->cache = [];
        return $this;
    }
}