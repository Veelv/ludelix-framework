<?php

namespace Ludelix\Tenant\Core;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Resolution\TenantResolver;
use Ludelix\Tenant\Isolation\DatabaseIsolation;
use Ludelix\Tenant\Isolation\CacheIsolation;
use Ludelix\Tenant\Security\TenantGuard;
use Ludelix\Tenant\Analytics\TenantMetrics;
use Ludelix\PRT\Request;

/**
 * Tenant Manager - Enterprise Multi-Tenancy Orchestration System
 * 
 * Central orchestration hub for all multi-tenancy operations within the Ludelix framework.
 * Provides comprehensive tenant lifecycle management, sophisticated resolution strategies,
 * and enterprise-grade isolation mechanisms.
 * 
 * Features:
 * - Multi-strategy tenant resolution (domain, subdomain, header, path)
 * - Database isolation with multiple strategies (separate DB, schema, row-level)
 * - Cache isolation with tenant-aware prefixing
 * - Security validation and cross-tenant protection
 * - Performance monitoring and analytics
 * - Hierarchical tenant relationships
 * - Resource quota management
 * 
 * @package Ludelix\Tenant\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantManager
{
    /**
     * Singleton instance for global tenant state management
     */
    protected static ?self $instance = null;

    /**
     * Current active tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Tenant resolution system
     */
    protected TenantResolver $resolver;

    /**
     * Database isolation manager
     */
    protected DatabaseIsolation $databaseIsolation;

    /**
     * Cache isolation manager
     */
    protected CacheIsolation $cacheIsolation;

    /**
     * Security guard for tenant validation
     */
    protected TenantGuard $guard;

    /**
     * Analytics and metrics collector
     */
    protected TenantMetrics $metrics;

    /**
     * Tenant context stack for nested switching
     */
    protected array $tenantStack = [];

    /**
     * Resolution cache for performance optimization
     */
    protected array $resolutionCache = [];

    /**
     * Initialize TenantManager with enterprise configuration
     * 
     * @param TenantResolver $resolver Multi-strategy tenant resolution system
     * @param DatabaseIsolation $databaseIsolation Database isolation manager
     * @param CacheIsolation $cacheIsolation Cache isolation manager
     * @param TenantGuard $guard Security validation system
     * @param TenantMetrics $metrics Analytics and monitoring
     */
    public function __construct(
        TenantResolver $resolver,
        DatabaseIsolation $databaseIsolation,
        CacheIsolation $cacheIsolation,
        TenantGuard $guard,
        TenantMetrics $metrics
    ) {
        $this->resolver = $resolver;
        $this->databaseIsolation = $databaseIsolation;
        $this->cacheIsolation = $cacheIsolation;
        $this->guard = $guard;
        $this->metrics = $metrics;
    }

    /**
     * Get singleton instance of TenantManager
     * 
     * @return self Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('TenantManager not initialized. Call initialize() first.');
        }
        return self::$instance;
    }

    /**
     * Initialize singleton instance with dependencies
     * 
     * @param array $dependencies Required dependencies for initialization
     * @return self Initialized instance
     */
    public static function initialize(array $dependencies): self
    {
        if (self::$instance === null) {
            self::$instance = new self(...$dependencies);
        }
        return self::$instance;
    }

    /**
     * Resolve tenant from HTTP request using multiple strategies
     * 
     * Applies resolution strategies in priority order:
     * 1. Domain-based (tenant.app.com)
     * 2. Subdomain (app.com/tenant)
     * 3. Header-based (X-Tenant-ID)
     * 4. Path-based (/tenant/dashboard)
     * 
     * @param Request $request HTTP request containing tenant identification
     * @param array $options Resolution options and strategy overrides
     * @return TenantInterface Resolved tenant instance
     * @throws \Exception If tenant cannot be resolved
     */
    public function resolve(Request $request, array $options = []): TenantInterface
    {
        $cacheKey = $this->generateCacheKey($request);
        
        // Check resolution cache for performance
        if (isset($this->resolutionCache[$cacheKey])) {
            $this->metrics->recordCacheHit('tenant_resolution');
            return $this->resolutionCache[$cacheKey];
        }

        // Resolve tenant using configured strategies
        $tenant = $this->resolver->resolve($request, $options);
        
        if (!$tenant) {
            throw new \Exception('Unable to resolve tenant from request');
        }

        // Validate tenant security and access
        $this->guard->validateAccess($tenant, $request);

        // Cache resolved tenant
        $this->resolutionCache[$cacheKey] = $tenant;
        
        // Record metrics
        $this->metrics->recordResolution($tenant, $request);

        return $tenant;
    }

    /**
     * Switch to specific tenant context with isolation setup
     * 
     * @param string|TenantInterface $tenant Tenant ID or instance
     * @param array $options Context switching options
     * @return self Fluent interface
     * @throws \Exception If tenant switching fails
     */
    public function switch($tenant, array $options = []): self
    {
        // Resolve tenant instance if ID provided
        if (is_string($tenant)) {
            $tenant = $this->findTenant($tenant);
        }

        // Validate tenant instance
        if (!$tenant instanceof TenantInterface) {
            throw new \Exception('Invalid tenant provided for context switching');
        }

        // Store current tenant in stack for nested switching
        if ($this->currentTenant !== null) {
            $this->tenantStack[] = $this->currentTenant;
        }

        // Switch to new tenant context
        $this->currentTenant = $tenant;

        // Configure database isolation
        $this->databaseIsolation->switchTenant($tenant);

        // Configure cache isolation
        $this->cacheIsolation->switchTenant($tenant);

        // Record tenant switch
        $this->metrics->recordTenantSwitch($tenant);

        return $this;
    }

    /**
     * Get current active tenant
     * 
     * @return TenantInterface|null Current tenant or null if none active
     */
    public function current(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    /**
     * Check if tenant context is currently active
     * 
     * @return bool True if tenant context is active
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Execute callback within specific tenant context
     * 
     * @param string|TenantInterface $tenant Tenant to switch to
     * @param callable $callback Callback to execute in tenant context
     * @return mixed Callback return value
     */
    public function withTenant($tenant, callable $callback): mixed
    {
        $previousTenant = $this->currentTenant;
        
        try {
            $this->switch($tenant);
            return $callback($this->currentTenant);
        } finally {
            // Restore previous tenant context
            if ($previousTenant) {
                $this->switch($previousTenant);
            } else {
                $this->currentTenant = null;
            }
        }
    }

    /**
     * Pop tenant from context stack (for nested switching)
     * 
     * @return self Fluent interface
     */
    public function pop(): self
    {
        if (!empty($this->tenantStack)) {
            $this->currentTenant = array_pop($this->tenantStack);
        } else {
            $this->currentTenant = null;
        }

        return $this;
    }

    /**
     * Get tenant-aware database isolation manager
     * 
     * @return DatabaseIsolation Database isolation instance
     */
    public function database(): DatabaseIsolation
    {
        return $this->databaseIsolation;
    }

    /**
     * Get tenant-aware cache isolation manager
     * 
     * @return CacheIsolation Cache isolation instance
     */
    public function cache(): CacheIsolation
    {
        return $this->cacheIsolation;
    }

    /**
     * Get tenant security guard
     * 
     * @return TenantGuard Security guard instance
     */
    public function guard(): TenantGuard
    {
        return $this->guard;
    }

    /**
     * Get tenant metrics and analytics
     * 
     * @return TenantMetrics Metrics instance
     */
    public function metrics(): TenantMetrics
    {
        return $this->metrics;
    }

    /**
     * Clear resolution cache for performance optimization
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->resolutionCache = [];
        return $this;
    }

    /**
     * Generate cache key for tenant resolution
     * 
     * @param Request $request HTTP request
     * @return string Cache key
     */
    protected function generateCacheKey(Request $request): string
    {
        return md5($request->getUri() . $request->getMethod() . serialize($request->getHeaders()));
    }

    /**
     * Find tenant by ID (placeholder for repository integration)
     * 
     * @param string $tenantId Tenant identifier
     * @return TenantInterface Tenant instance
     * @throws \Exception If tenant not found
     */
    protected function findTenant(string $tenantId): TenantInterface
    {
        // This would integrate with tenant repository/cubby
        throw new \Exception("Tenant not found: {$tenantId}");
    }
}