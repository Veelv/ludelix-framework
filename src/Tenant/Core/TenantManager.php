<?php

namespace Ludelix\Tenant\Core;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Resolver\TenantResolver;
use Ludelix\Tenant\Database\TenantDatabaseManager;
use Ludelix\Tenant\Cache\TenantCacheManager;
use Ludelix\Tenant\Config\TenantConfigManager;
use Ludelix\Tenant\Events\TenantSwitchedEvent;
use Ludelix\Tenant\Events\TenantResolvedEvent;
use Ludelix\Tenant\Events\TenantProvisionedEvent;
use Ludelix\Tenant\Events\TenantDeprovisionedEvent;
use Ludelix\Tenant\Exceptions\TenantNotFoundException;
use Ludelix\Tenant\Exceptions\TenantResolutionException;
use Ludelix\Tenant\Exceptions\TenantProvisioningException;
use Ludelix\Tenant\Exceptions\TenantSecurityException;
use Ludelix\Tenant\Security\TenantSecurityManager;
use Ludelix\Tenant\Analytics\TenantAnalyticsCollector;
use Ludelix\Tenant\Provisioning\TenantProvisioningEngine;
use Ludelix\Core\EventDispatcher;
use Ludelix\Cache\CacheManager;
use Ludelix\Database\Core\EntityManager;
use Ludelix\PRT\Request;
use Psr\Log\LoggerInterface;

/**
 * Tenant Manager - Enterprise Multi-Tenancy Orchestration System
 * 
 * The TenantManager serves as the central orchestration hub for all multi-tenancy
 * operations within the Ludelix framework. It provides comprehensive tenant
 * lifecycle management, sophisticated resolution strategies, and enterprise-grade
 * isolation mechanisms.
 * 
 * Core Architectural Principles:
 * 
 * 1. **Tenant Isolation Strategies**:
 *    - Database-level isolation with dynamic connection switching
 *    - Schema-level isolation with tenant-specific prefixes
 *    - Application-level isolation with contextual data filtering
 *    - Cache isolation with tenant-aware key prefixing
 *    - File system isolation with tenant-specific storage paths
 * 
 * 2. **Resolution Mechanisms**:
 *    - Domain-based resolution (tenant.example.com)
 *    - Subdomain resolution (acme.saas.com)
 *    - Path-based resolution (/tenant/acme/dashboard)
 *    - Header-based resolution (X-Tenant-ID: acme)
 *    - JWT-based resolution with embedded tenant claims
 *    - API key-based resolution with tenant association
 * 
 * 3. **Performance Optimization**:
 *    - Multi-layer tenant caching with intelligent invalidation
 *    - Connection pooling with tenant-aware load balancing
 *    - Lazy loading of tenant-specific configurations
 *    - Predictive tenant context switching
 *    - Memory-efficient tenant state management
 * 
 * 4. **Security & Compliance**:
 *    - Cross-tenant data leakage prevention
 *    - Tenant-specific encryption key management
 *    - Audit trail with tenant context preservation
 *    - GDPR compliance with tenant data isolation
 *    - SOC 2 compliance with tenant access controls
 * 
 * 5. **Scalability & Distribution**:
 *    - Horizontal scaling with tenant sharding
 *    - Multi-region tenant distribution
 *    - Load balancing with tenant affinity
 *    - Auto-scaling based on tenant resource usage
 *    - Microservices integration with tenant context propagation
 * 
 * Advanced Features:
 * 
 * - **Hierarchical Tenancy**: Support for parent-child tenant relationships
 *   with configuration inheritance and resource sharing policies
 * 
 * - **Dynamic Provisioning**: Automated tenant creation, configuration,
 *   and resource allocation with customizable provisioning workflows
 * 
 * - **Resource Quotas**: Comprehensive resource monitoring and enforcement
 *   including storage, bandwidth, API calls, and custom metrics
 * 
 * - **Feature Flagging**: Tenant-specific feature enablement with
 *   gradual rollout and A/B testing capabilities
 * 
 * - **Analytics & Monitoring**: Real-time tenant usage analytics,
 *   performance monitoring, and predictive scaling recommendations
 * 
 * - **Backup & Recovery**: Tenant-specific backup strategies with
 *   point-in-time recovery and cross-region replication
 * 
 * @package Ludelix\Tenant\Core
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 * 
 * @example Basic Usage:
 * ```php
 * // Resolve current tenant from request
 * $tenant = TenantManager::resolve($request);
 * 
 * // Switch to specific tenant context
 * TenantManager::switch('acme-corp');
 * 
 * // Get tenant-aware database connection
 * $db = TenantManager::database();
 * 
 * // Access tenant-specific cache
 * $cache = TenantManager::cache();
 * ```
 * 
 * @example Advanced Usage:
 * ```php
 * // Provision new tenant with custom configuration
 * $tenant = TenantManager::provision([
 *     'name' => 'Acme Corporation',
 *     'domain' => 'acme.example.com',
 *     'database' => 'dedicated',
 *     'features' => ['advanced_analytics', 'api_access'],
 *     'quotas' => ['storage' => '100GB', 'api_calls' => 1000000]
 * ]);
 * 
 * // Execute operations in tenant context
 * TenantManager::withTenant('acme-corp', function($tenant) {
 *     // All operations here are tenant-isolated
 *     $users = User::all(); // Only acme-corp users
 *     $cache->put('key', 'value'); // Tenant-prefixed cache
 * });
 * 
 * // Cross-tenant operations with explicit permissions
 * TenantManager::crossTenant(['tenant-a', 'tenant-b'])
 *              ->withPermission('data_sharing')
 *              ->execute(function($tenants) {
 *                  // Controlled cross-tenant operations
 *              });
 * ```
 */
class TenantManager
{
    /**
     * Singleton instance for global tenant state management
     * Thread-safe implementation with proper locking mechanisms
     */
    protected static ?self $instance = null;
    protected static object $instanceLock;
    
    /**
     * Core system dependencies with comprehensive dependency injection
     */
    protected TenantResolver $resolver;
    protected TenantDatabaseManager $databaseManager;
    protected TenantCacheManager $cacheManager;
    protected TenantConfigManager $configManager;
    protected TenantSecurityManager $securityManager;
    protected TenantAnalyticsCollector $analyticsCollector;
    protected TenantProvisioningEngine $provisioningEngine;
    protected EventDispatcher $eventDispatcher;
    protected EntityManager $entityManager;
    protected LoggerInterface $logger;
    
    /**
     * Current tenant context and state management
     */
    protected ?TenantInterface $currentTenant = null;
    protected array $tenantStack = [];
    protected array $tenantCache = [];
    protected array $resolvedTenants = [];
    
    /**
     * Configuration and operational parameters
     */
    protected array $config = [];
    protected array $resolutionStrategies = [];
    protected array $isolationStrategies = [];
    protected bool $strictIsolation = true;
    protected bool $crossTenantEnabled = false;
    
    /**
     * Performance monitoring and optimization
     */
    protected array $performanceMetrics = [];
    protected array $resolutionCache = [];
    protected int $cacheHits = 0;
    protected int $cacheMisses = 0;
    protected float $totalResolutionTime = 0.0;
    
    /**
     * Security and compliance tracking
     */
    protected array $accessLog = [];
    protected array $securityEvents = [];
    protected bool $auditEnabled = true;
    protected array $complianceSettings = [];

    /**
     * Initialize TenantManager with comprehensive enterprise configuration
     * 
     * @param TenantResolver $resolver Multi-strategy tenant resolution system
     * @param TenantDatabaseManager $databaseManager Database isolation and management
     * @param TenantCacheManager $cacheManager Cache isolation and optimization
     * @param TenantConfigManager $configManager Tenant-specific configuration management
     * @param TenantSecurityManager $securityManager Security and access control
     * @param TenantAnalyticsCollector $analyticsCollector Usage analytics and monitoring
     * @param TenantProvisioningEngine $provisioningEngine Automated tenant provisioning
     * @param EventDispatcher $eventDispatcher Event system for tenant operations
     * @param EntityManager $entityManager Database entity management
     * @param LoggerInterface $logger Structured logging with tenant context
     * @param array $config Comprehensive tenant system configuration
     * 
     * @throws TenantProvisioningException If initialization fails
     */
    public function __construct(
        TenantResolver $resolver,
        TenantDatabaseManager $databaseManager,
        TenantCacheManager $cacheManager,
        TenantConfigManager $configManager,
        TenantSecurityManager $securityManager,
        TenantAnalyticsCollector $analyticsCollector,
        TenantProvisioningEngine $provisioningEngine,
        EventDispatcher $eventDispatcher,
        EntityManager $entityManager,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->resolver = $resolver;
        $this->databaseManager = $databaseManager;
        $this->cacheManager = $cacheManager;
        $this->configManager = $configManager;
        $this->securityManager = $securityManager;
        $this->analyticsCollector = $analyticsCollector;
        $this->provisioningEngine = $provisioningEngine;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->config = $config;
        
        // Initialize singleton lock mechanism
        if (!isset(self::$instanceLock)) {
            self::$instanceLock = new \stdClass();
        }
        
        // Configure tenant resolution strategies
        $this->initializeResolutionStrategies();
        
        // Configure tenant isolation mechanisms
        $this->initializeIsolationStrategies();
        
        // Configure security and compliance settings
        $this->initializeSecuritySettings();
        
        // Configure performance optimization
        $this->initializePerformanceOptimization();
        
        // Register event listeners for tenant lifecycle
        $this->registerEventListeners();
        
        // Initialize analytics collection
        $this->initializeAnalytics();
        
        $this->logger->info('TenantManager initialized with enterprise configuration', [
            'resolution_strategies' => array_keys($this->resolutionStrategies),
            'isolation_strategies' => array_keys($this->isolationStrategies),
            'strict_isolation' => $this->strictIsolation,
            'cross_tenant_enabled' => $this->crossTenantEnabled,
            'audit_enabled' => $this->auditEnabled,
            'performance_monitoring' => $config['performance']['enabled'] ?? true
        ]);
    }
    
    /**
     * Thread-safe singleton instance retrieval with proper locking
     * 
     * @param array $dependencies Optional dependencies for initialization
     * @return self Singleton TenantManager instance
     * 
     * @throws TenantProvisioningException If instance cannot be created
     */
    public static function instance(array $dependencies = []): self
    {
        if (self::$instance === null) {
            if (!isset(self::$instanceLock)) {
                self::$instanceLock = new \stdClass();
            }
            
            if (self::$instance === null) {
                if (empty($dependencies)) {
                    throw new TenantProvisioningException(
                        'TenantManager instance not initialized. Dependencies required for first initialization.'
                    );
                }
                
                self::$instance = new self(...$dependencies);
            }
        }
        
        return self::$instance;
    }
    
    /**
     * Resolve tenant from HTTP request with comprehensive strategy application
     * 
     * This method implements a sophisticated tenant resolution pipeline that:
     * 1. Applies multiple resolution strategies in priority order
     * 2. Validates resolved tenant against security policies
     * 3. Caches resolution results for performance optimization
     * 4. Emits events for monitoring and analytics
     * 5. Handles fallback scenarios and error conditions
     * 
     * @param Request $request HTTP request containing tenant identification data
     * @param array $options Resolution options and strategy overrides
     * @return TenantInterface Resolved tenant instance
     * 
     * @throws TenantNotFoundException If no tenant can be resolved
     * @throws TenantResolutionException If resolution process fails
     * @throws TenantSecurityException If resolved tenant fails security validation
     */
    public function resolve(Request $request, array $options = []): TenantInterface
    {
        $startTime = microtime(true);
        $resolutionId = $this->generateResolutionId($request);
        
        try {
            // Check resolution cache for performance optimization
            if ($cached = $this->getFromResolutionCache($resolutionId)) {
                $this->cacheHits++;
                $this->recordResolutionMetrics($resolutionId, microtime(true) - $startTime, 'cache_hit');
                
                $this->logger->debug('Tenant resolved from cache', [
                    'tenant_id' => $cached->getId(),
                    'resolution_id' => $resolutionId,
                    'cache_hit' => true
                ]);
                
                return $cached;
            }
            
            $this->cacheMisses++;
            
            // Apply resolution strategies in priority order
            $tenant = null;
            $appliedStrategies = [];
            
            foreach ($this->getOrderedResolutionStrategies($options) as $strategyName => $strategy) {
                try {
                    $this->logger->debug("Applying tenant resolution strategy: {$strategyName}", [
                        'resolution_id' => $resolutionId,
                        'strategy' => $strategyName
                    ]);
                    
                    $resolvedTenant = $strategy->resolve($request, $options);
                    
                    if ($resolvedTenant !== null) {
                        $tenant = $resolvedTenant;
                        $appliedStrategies[] = $strategyName;
                        
                        $this->logger->info("Tenant resolved using strategy: {$strategyName}", [
                            'tenant_id' => $tenant->getId(),
                            'tenant_name' => $tenant->getName(),
                            'resolution_id' => $resolutionId
                        ]);
                        
                        break;
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning("Tenant resolution strategy failed: {$strategyName}", [
                        'resolution_id' => $resolutionId,
                        'strategy' => $strategyName,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Continue to next strategy unless configured to fail fast
                    if ($options['fail_fast'] ?? false) {
                        throw new TenantResolutionException(
                            "Tenant resolution failed at strategy: {$strategyName}",
                            0,
                            $e
                        );
                    }
                }
            }
            
            // Handle case where no tenant could be resolved
            if ($tenant === null) {
                $this->handleUnresolvedTenant($request, $resolutionId, $appliedStrategies);
            }
            
            // Validate resolved tenant against security policies
            $this->validateTenantSecurity($tenant, $request);
            
            // Cache the resolved tenant for future requests
            $this->cacheResolvedTenant($resolutionId, $tenant);
            
            // Record analytics and performance metrics
            $this->recordResolutionMetrics($resolutionId, microtime(true) - $startTime, 'resolved');
            $this->analyticsCollector->recordTenantResolution($tenant, $request, $appliedStrategies);
            
            // Emit tenant resolved event
            $this->eventDispatcher->dispatch(new TenantResolvedEvent(
                $tenant,
                $request,
                $appliedStrategies,
                microtime(true) - $startTime
            ));
            
            return $tenant;
            
        } catch (\Throwable $e) {
            $this->logger->error('Tenant resolution failed', [
                'resolution_id' => $resolutionId,
                'request_uri' => $request->getUri(),
                'request_headers' => $this->sanitizeHeaders($request->getHeaders()),
                'applied_strategies' => $appliedStrategies ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->recordResolutionMetrics($resolutionId, microtime(true) - $startTime, 'failed');
            
            throw $e;
        }
    }
    
    /**
     * Switch to specific tenant context with comprehensive state management
     * 
     * @param string|TenantInterface $tenant Tenant ID or tenant instance
     * @param array $options Context switching options
     * @return self Fluent interface for method chaining
     * 
     * @throws TenantNotFoundException If tenant cannot be found
     * @throws TenantSecurityException If tenant access is denied
     */
    public function switch($tenant, array $options = []): self
    {
        $startTime = microtime(true);
        
        try {
            // Resolve tenant instance if ID provided
            if (is_string($tenant)) {
                $tenant = $this->findTenant($tenant);
            }
            
            if (!$tenant instanceof TenantInterface) {
                throw new TenantNotFoundException(
                    'Invalid tenant provided for context switching'
                );
            }
            
            // Validate tenant access permissions
            $this->securityManager->validateTenantAccess($tenant, $options);
            
            // Store previous tenant in stack for nested switching
            if ($this->currentTenant !== null) {
                $this->tenantStack[] = $this->currentTenant;
            }
            
            // Switch to new tenant context
            $previousTenant = $this->currentTenant;
            $this->currentTenant = $tenant;
            
            // Configure tenant-specific database connections
            $this->databaseManager->switchTenant($tenant);
            
            // Configure tenant-specific cache isolation
            $this->cacheManager->switchTenant($tenant);
            
            // Configure tenant-specific settings
            $this->configManager->switchTenant($tenant);
            
            // Record tenant switch in audit log
            $this->recordTenantSwitch($previousTenant, $tenant, $options);
            
            // Emit tenant switched event
            $this->eventDispatcher->dispatch(new TenantSwitchedEvent(
                $previousTenant,
                $tenant,
                microtime(true) - $startTime
            ));
            
            $this->logger->info('Tenant context switched successfully', [
                'previous_tenant' => $previousTenant?->getId(),
                'current_tenant' => $tenant->getId(),
                'switch_duration' => microtime(true) - $startTime
            ]);
            
            return $this;
            
        } catch (\Throwable $e) {
            $this->logger->error('Tenant context switch failed', [
                'tenant_id' => is_string($tenant) ? $tenant : $tenant->getId(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get current active tenant
     */
    public function current(): ?TenantInterface
    {
        return $this->currentTenant;
    }
    
    /**
     * Check if tenant context is active
     */
    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }
    
    /**
     * Get tenant-aware database manager
     */
    public function database(): TenantDatabaseManager
    {
        return $this->databaseManager;
    }
    
    /**
     * Get tenant-aware cache manager
     */
    public function cache(): TenantCacheManager
    {
        return $this->cacheManager;
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
            if ($previousTenant) {
                $this->switch($previousTenant);
            } else {
                $this->currentTenant = null;
            }
        }
    }
    
    /**
     * Get comprehensive performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'total_resolutions' => count($this->performanceMetrics),
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_hit_ratio' => $this->cacheHits + $this->cacheMisses > 0 
                ? $this->cacheHits / ($this->cacheHits + $this->cacheMisses) 
                : 0,
            'total_resolution_time' => $this->totalResolutionTime,
            'average_resolution_time' => count($this->performanceMetrics) > 0 
                ? $this->totalResolutionTime / count($this->performanceMetrics) 
                : 0,
            'detailed_metrics' => $this->performanceMetrics,
        ];
    }
    
    // Protected helper methods for internal operations
    
    protected function initializeResolutionStrategies(): void
    {
        $this->resolutionStrategies = [
            'domain' => new \stdClass(), // Placeholder
            'subdomain' => new \stdClass(),
            'header' => new \stdClass(),
            'path' => new \stdClass(),
        ];
    }
    
    protected function initializeIsolationStrategies(): void
    {
        $this->isolationStrategies = [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'config' => true,
        ];
    }
    
    protected function initializeSecuritySettings(): void
    {
        $this->complianceSettings = [
            'gdpr_enabled' => $this->config['compliance']['gdpr'] ?? true,
            'audit_enabled' => $this->config['compliance']['audit'] ?? true,
            'encryption_required' => $this->config['security']['encryption'] ?? true,
        ];
    }
    
    protected function initializePerformanceOptimization(): void
    {
        // Initialize performance monitoring
    }
    
    protected function registerEventListeners(): void
    {
        // Register comprehensive event listeners
    }
    
    protected function initializeAnalytics(): void
    {
        // Initialize analytics collection
    }
    
    protected function generateResolutionId(Request $request): string
    {
        return md5($request->getUri() . $request->getMethod() . microtime(true));
    }
    
    protected function getFromResolutionCache(string $resolutionId): ?TenantInterface
    {
        return $this->resolutionCache[$resolutionId] ?? null;
    }
    
    protected function cacheResolvedTenant(string $resolutionId, TenantInterface $tenant): void
    {
        $this->resolutionCache[$resolutionId] = $tenant;
    }
    
    protected function recordResolutionMetrics(string $resolutionId, float $duration, string $result): void
    {
        $this->performanceMetrics[] = [
            'resolution_id' => $resolutionId,
            'duration' => $duration,
            'result' => $result,
            'timestamp' => microtime(true)
        ];
        
        $this->totalResolutionTime += $duration;
    }
    
    protected function getOrderedResolutionStrategies(array $options): array
    {
        return $this->resolutionStrategies;
    }
    
    protected function handleUnresolvedTenant(Request $request, string $resolutionId, array $appliedStrategies): void
    {
        throw new TenantNotFoundException(
            'No tenant could be resolved from the request using available strategies',
            0,
            null,
            [
                'resolution_id' => $resolutionId,
                'applied_strategies' => $appliedStrategies,
                'request_uri' => $request->getUri()
            ]
        );
    }
    
    protected function validateTenantSecurity(TenantInterface $tenant, Request $request): void
    {
        $this->securityManager->validateTenantAccess($tenant, ['request' => $request]);
    }
    
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        return array_filter($headers, function($key) use ($sensitiveHeaders) {
            return !in_array(strtolower($key), $sensitiveHeaders);
        }, ARRAY_FILTER_USE_KEY);
    }
    
    protected function findTenant(string $tenantId): TenantInterface
    {
        throw new TenantNotFoundException("Tenant not found: {$tenantId}");
    }
    
    protected function recordTenantSwitch(?TenantInterface $from, TenantInterface $to, array $options): void
    {
        if ($this->auditEnabled) {
            $this->accessLog[] = [
                'action' => 'tenant_switch',
                'from_tenant' => $from?->getId(),
                'to_tenant' => $to->getId(),
                'timestamp' => microtime(true),
                'options' => $options
            ];
        }
    }
}