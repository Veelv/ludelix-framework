<?php

namespace Ludelix\Tenant\Resolution\Strategies;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\PRT\Request;

/**
 * Header Strategy - Header-based Tenant Resolution
 * 
 * Resolves tenants based on HTTP headers (e.g., X-Tenant-ID, X-Tenant-Name).
 * Commonly used for API requests and mobile applications.
 * 
 * @package Ludelix\Tenant\Resolution\Strategies
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class HeaderStrategy
{
    /**
     * Default header names to check for tenant identification
     */
    protected array $headerNames = [
        'X-Tenant-ID',
        'X-Tenant-Name', 
        'Tenant-ID',
        'Tenant'
    ];

    /**
     * Header resolution cache
     */
    protected array $cache = [];

    /**
     * Initialize strategy with configuration
     * 
     * @param array $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        if (isset($config['headers'])) {
            $this->headerNames = $config['headers'];
        }
    }

    /**
     * Resolve tenant from HTTP headers
     * 
     * @param Request $request HTTP request to analyze
     * @param array $options Resolution options
     * @return TenantInterface|null Resolved tenant or null
     */
    public function resolve(Request $request, array $options = []): ?TenantInterface
    {
        // Get headers to check (allow override)
        $headersToCheck = $options['headers'] ?? $this->headerNames;
        
        foreach ($headersToCheck as $headerName) {
            $tenantId = $request->getHeader($headerName);
            
            if ($tenantId) {
                // Check cache first
                $cacheKey = strtolower($headerName) . ':' . $tenantId;
                if (isset($this->cache[$cacheKey])) {
                    return $this->cache[$cacheKey];
                }

                // Validate tenant ID format
                if ($this->isValidTenantId($tenantId)) {
                    $tenant = $this->createTenantFromHeader($tenantId, $headerName);
                    
                    // Cache result
                    $this->cache[$cacheKey] = $tenant;
                    
                    return $tenant;
                }
            }
        }

        return null;
    }

    /**
     * Validate tenant ID format
     * 
     * @param string $tenantId Tenant identifier from header
     * @return bool True if valid format
     */
    protected function isValidTenantId(string $tenantId): bool
    {
        // Basic validation - alphanumeric, hyphens, underscores
        return preg_match('/^[a-zA-Z0-9\-_]+$/', $tenantId) && strlen($tenantId) <= 64;
    }

    /**
     * Create tenant instance from header value
     * 
     * @param string $tenantId Tenant identifier
     * @param string $headerName Source header name
     * @return TenantInterface Tenant instance
     */
    protected function createTenantFromHeader(string $tenantId, string $headerName): TenantInterface
    {
        return new Tenant([
            'id' => $tenantId,
            'name' => ucfirst(str_replace(['-', '_'], ' ', $tenantId)),
            'status' => 'active',
            'metadata' => [
                'resolved_from' => 'header',
                'header_name' => $headerName
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
     * Add header name to check for tenant identification
     * 
     * @param string $headerName Header name to add
     * @return self Fluent interface
     */
    public function addHeader(string $headerName): self
    {
        if (!in_array($headerName, $this->headerNames)) {
            $this->headerNames[] = $headerName;
        }
        return $this;
    }

    /**
     * Set header names to check for tenant identification
     * 
     * @param array $headerNames Array of header names
     * @return self Fluent interface
     */
    public function setHeaders(array $headerNames): self
    {
        $this->headerNames = $headerNames;
        return $this;
    }

    /**
     * Get configured header names
     * 
     * @return array Header names
     */
    public function getHeaders(): array
    {
        return $this->headerNames;
    }

    /**
     * Clear header resolution cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->cache = [];
        return $this;
    }
}