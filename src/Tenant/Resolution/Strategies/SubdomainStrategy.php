<?php

namespace Ludelix\Tenant\Resolution\Strategies;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\PRT\Request;

/**
 * Subdomain Strategy - Subdomain-based Tenant Resolution
 * 
 * Resolves tenants based on subdomain patterns (e.g., app.com/tenant1, app.com/tenant2).
 * Supports path-based tenant identification with configurable patterns.
 * 
 * @package Ludelix\Tenant\Resolution\Strategies
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class SubdomainStrategy
{
    /**
     * Path patterns for tenant identification
     */
    protected array $pathPatterns = [
        '/^\/([a-zA-Z0-9\-_]+)\/.*$/',
        '/^\/tenant\/([a-zA-Z0-9\-_]+)\/.*$/',
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
        if (isset($config['patterns'])) {
            $this->pathPatterns = $config['patterns'];
        }
    }

    /**
     * Resolve tenant from URL path
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

        // Try each pattern
        foreach ($this->pathPatterns as $pattern) {
            if (preg_match($pattern, $path, $matches)) {
                $tenantId = $matches[1];
                
                if ($this->isValidTenantId($tenantId)) {
                    $tenant = $this->createTenantFromPath($tenantId, $path);
                    $this->cache[$path] = $tenant;
                    return $tenant;
                }
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
                'resolved_from' => 'subdomain',
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
     * Add path pattern for tenant resolution
     * 
     * @param string $pattern Regex pattern
     * @return self Fluent interface
     */
    public function addPattern(string $pattern): self
    {
        $this->pathPatterns[] = $pattern;
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