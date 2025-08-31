<?php

namespace Ludelix\Tenant\Resolution\Strategies;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\PRT\Request;

/**
 * Domain Strategy - Domain-based Tenant Resolution
 * 
 * Resolves tenants based on domain names (e.g., tenant1.app.com, tenant2.app.com).
 * Supports both subdomain and custom domain configurations.
 * 
 * @package Ludelix\Tenant\Resolution\Strategies
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class DomainStrategy
{
    /**
     * Base domain for subdomain matching
     */
    protected string $baseDomain = '';

    /**
     * Domain-to-tenant mapping cache
     */
    protected array $domainCache = [];

    /**
     * Initialize strategy with configuration
     * 
     * @param array $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->baseDomain = $config['base_domain'] ?? '';
    }

    /**
     * Resolve tenant from domain in HTTP request
     * 
     * @param Request $request HTTP request to analyze
     * @param array $options Resolution options
     * @return TenantInterface|null Resolved tenant or null
     */
    public function resolve(Request $request, array $options = []): ?TenantInterface
    {
        $host = $request->server('HTTP_HOST') ?? $request->server('SERVER_NAME');
        
        if (!$host) {
            return null;
        }

        // Check cache first
        if (isset($this->domainCache[$host])) {
            return $this->domainCache[$host];
        }

        // Try subdomain resolution first
        $tenant = $this->resolveFromSubdomain($host);
        
        // Try custom domain resolution if subdomain fails
        if (!$tenant) {
            $tenant = $this->resolveFromCustomDomain($host);
        }

        // Cache result
        if ($tenant) {
            $this->domainCache[$host] = $tenant;
        }

        return $tenant;
    }

    /**
     * Resolve tenant from subdomain (e.g., tenant1.app.com)
     * 
     * @param string $host Request host
     * @return TenantInterface|null Resolved tenant
     */
    protected function resolveFromSubdomain(string $host): ?TenantInterface
    {
        if (empty($this->baseDomain)) {
            return null;
        }

        // Check if host matches subdomain pattern
        if (!str_ends_with($host, '.' . $this->baseDomain)) {
            return null;
        }

        // Extract subdomain
        $subdomain = str_replace('.' . $this->baseDomain, '', $host);
        
        // Skip www and other common prefixes
        if (in_array($subdomain, ['www', 'api', 'admin', 'mail'])) {
            return null;
        }

        // Validate subdomain format
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $subdomain)) {
            return null;
        }

        // This would typically query a database or repository
        // For now, create a basic tenant instance
        return $this->createTenantFromSubdomain($subdomain, $host);
    }

    /**
     * Resolve tenant from custom domain
     * 
     * @param string $host Request host
     * @return TenantInterface|null Resolved tenant
     */
    protected function resolveFromCustomDomain(string $host): ?TenantInterface
    {
        // This would typically query a database for custom domain mappings
        // For now, return null as custom domains require database integration
        return null;
    }

    /**
     * Create tenant instance from subdomain
     * 
     * @param string $subdomain Extracted subdomain
     * @param string $host Full host
     * @return TenantInterface Tenant instance
     */
    protected function createTenantFromSubdomain(string $subdomain, string $host): TenantInterface
    {
        return new Tenant([
            'id' => $subdomain,
            'name' => ucfirst($subdomain),
            'domain' => [
                'primary' => $host,
                'subdomain' => $subdomain
            ],
            'status' => 'active',
            'database' => [
                'strategy' => 'prefix',
                'prefix' => $subdomain . '_'
            ],
            'cache' => [
                'prefix' => "tenant:{$subdomain}:"
            ]
        ]);
    }

    /**
     * Set base domain for subdomain matching
     * 
     * @param string $domain Base domain
     * @return self Fluent interface
     */
    public function setBaseDomain(string $domain): self
    {
        $this->baseDomain = $domain;
        return $this;
    }

    /**
     * Clear domain resolution cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->domainCache = [];
        return $this;
    }
}