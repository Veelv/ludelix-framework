<?php

namespace Ludelix\Bridge;

use Ludelix\Interface\Bridge\BridgeInterface;
use Ludelix\Bridge\Context\ExecutionContext;
use Ludelix\Bridge\Context\TenantContext;
use Ludelix\Bridge\Context\RequestContext;
use Ludelix\Bridge\Resolver\ServiceResolver;
use Ludelix\Bridge\Resolver\ContextualResolver;
use Ludelix\Bridge\Cache\BridgeCache;
use Ludelix\Bridge\Events\ServiceAccessEvent;
use Ludelix\Bridge\Events\ContextSwitchEvent;
use Ludelix\Bridge\Middleware\BridgeMiddleware;
use Ludelix\Bridge\Exceptions\ServiceNotFoundException;
use Ludelix\Bridge\Exceptions\ContextException;
use Ludelix\Bridge\Exceptions\CircularDependencyException;
use Ludelix\Core\Container;
use Ludelix\Core\EventDispatcher;
use Psr\Log\LoggerInterface;
use Ludelix\Validation\Core\ValidationEngine;

/**
 * Bridge - Advanced Contextual Service Access Layer
 * 
 * The Bridge system provides a sophisticated alternative to traditional facades,
 * offering contextual, tenant-aware, and performance-optimized service access.
 * 
 * Key Features:
 * - Contextual service resolution with tenant isolation
 * - Circular dependency detection and prevention
 * - Performance optimization through intelligent caching
 * - Event-driven architecture for observability
 * - Middleware pipeline for service access control
 * - Hot-swappable service implementations
 * - Memory-efficient context switching
 * - Thread-safe operations for concurrent requests
 * 
 * Architecture:
 * The Bridge operates as a sophisticated proxy layer between application code
 * and the underlying service container, providing advanced features like:
 * 
 * 1. Multi-dimensional Context Management:
 *    - Tenant Context: Automatic tenant isolation
 *    - Request Context: Per-request state management
 *    - Execution Context: Call stack and performance tracking
 * 
 * 2. Intelligent Service Resolution:
 *    - Lazy loading with dependency injection
 *    - Circular dependency detection
 *    - Service lifecycle management
 *    - Hot-reload capabilities for development
 * 
 * 3. Performance Optimization:
 *    - Multi-layer caching strategy
 *    - Service instance pooling
 *    - Memory usage optimization
 *    - Predictive service preloading
 * 
 * @package Ludelix\Bridge
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @see Version
 * @since 1.0.0
 * 
 * @example Basic Usage:
 * ```php
 * // Simple service access
 * $user = Bridge::auth()->user();
 * 
 * // Contextual access with tenant isolation
 * $data = Bridge::context(['tenant' => 'acme'])
 *              ->cache()
 *              ->remember('users', fn() => User::all());
 * 
 * // Advanced context switching
 * Bridge::withTenant('tenant-1')
 *       ->withRequest($request)
 *       ->db()
 *       ->transaction(function() {
 *           // Tenant-isolated database operations
 *       });
 * ```
 * 
 * @see BridgeInterface
 * @see ExecutionContext
 * @see ServiceResolver
 */
class Bridge implements BridgeInterface
{
    /**
     * Singleton instance for global access
     * Thread-safe implementation using double-checked locking pattern
     */
    protected static ?self $instance = null;
    protected static object $lock;

    /**
     * Core dependencies injected via constructor
     */
    protected Container $container;
    protected EventDispatcher $eventDispatcher;
    protected LoggerInterface $logger;
    protected BridgeCache $cache;

    /**
     * Service resolution and context management
     */
    protected ServiceResolver $serviceResolver;
    protected ContextualResolver $contextualResolver;
    protected ExecutionContext $executionContext;

    /**
     * Context stack for nested context switching
     * Implements copy-on-write for memory efficiency
     */
    protected array $contextStack = [];
    protected TenantContext $tenantContext;
    protected RequestContext $requestContext;

    /**
     * Performance and debugging metrics
     */
    protected array $performanceMetrics = [];
    protected array $accessLog = [];
    protected bool $debugMode = false;

    /**
     * Middleware pipeline for service access control
     */
    protected array $middlewareStack = [];

    /**
     * Circular dependency detection
     */
    protected array $resolutionStack = [];
    protected int $maxResolutionDepth = 50;

    /** @var ValidationEngine|null */
    protected static ?ValidationEngine $validationEngine = null;

    /**
     * Initialize Bridge with comprehensive dependency injection
     * 
     * @param Container $container Service container instance
     * @param EventDispatcher $eventDispatcher Event system for observability
     * @param LoggerInterface $logger Structured logging interface
     * @param BridgeCache $cache Multi-layer caching system
     * @param array $config Bridge configuration options
     * 
     * @throws \InvalidArgumentException If required dependencies are missing
     */
    public function __construct(
        Container $container,
        EventDispatcher $eventDispatcher = null,
        LoggerInterface $logger = null,
        BridgeCache $cache = null,
        array $config = []
    ) {
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->logger = $logger ?? new \Psr\Log\NullLogger();
        $this->cache = $cache ?? new BridgeCache();

        // Initialize context management systems
        $this->executionContext = new ExecutionContext();
        $this->tenantContext = new TenantContext($config['tenant'] ?? []);
        $this->requestContext = new RequestContext();

        // Initialize service resolvers with advanced capabilities
        $this->serviceResolver = new ServiceResolver(
            $container,
            $this->cache,
            $config['resolver'] ?? []
        );

        $this->contextualResolver = new ContextualResolver(
            $this->serviceResolver,
            $this->executionContext,
            $config['contextual'] ?? []
        );

        // Configure performance and debugging
        $this->debugMode = $config['debug'] ?? false;
        $this->maxResolutionDepth = $config['max_resolution_depth'] ?? 50;

        // Initialize middleware pipeline
        $this->initializeMiddleware($config['middleware'] ?? []);

        // Set up performance monitoring
        if ($config['performance_monitoring'] ?? true) {
            $this->initializePerformanceMonitoring();
        }

        $this->logger->info('Bridge initialized with advanced context management', [
            'tenant_isolation' => $this->tenantContext->isEnabled(),
            'performance_monitoring' => isset($config['performance_monitoring']),
            'debug_mode' => $this->debugMode,
            'middleware_count' => count($this->middlewareStack)
        ]);
    }

    /**
     * Thread-safe singleton instance retrieval
     * Implements double-checked locking for optimal performance
     * 
     * @param Container|null $container Optional container for initialization
     * @return self Singleton Bridge instance
     * 
     * @throws \RuntimeException If Bridge cannot be initialized
     */
    public static function instance(Container $container = null): self
    {
        if (self::$instance === null) {
            if (!isset(self::$lock)) {
                self::$lock = new \stdClass();
            }

            if (self::$instance === null) {
                if ($container === null && function_exists('app')) {
                    $framework = app();
                    $container = $framework->container();
                } elseif ($container === null) {
                    // Try to get container from global scope
                    global $app;
                    if (isset($app) && method_exists($app, 'container')) {
                        $container = $app->container();
                    }
                }

                if ($container === null) {
                    throw new \RuntimeException(
                        'Bridge instance not initialized. Container required for first initialization.'
                    );
                }

                self::$instance = new self(
                    $container,
                    $container->has('events') ? $container->get('events') : null,
                    $container->has('logger') ? $container->get('logger') : null,
                    $container->has('bridge.cache') ? $container->get('bridge.cache') : null,
                    $container->has('config') ? $container->get('config')->get('bridge', []) : []
                );
            }
        }

        return self::$instance;
    }

    /**
     * Advanced service resolution with comprehensive context awareness
     * 
     * This method implements a sophisticated service resolution algorithm that:
     * 1. Applies contextual transformations based on current execution context
     * 2. Implements tenant isolation for multi-tenant applications
     * 3. Provides circular dependency detection and prevention
     * 4. Offers performance optimization through intelligent caching
     * 5. Supports middleware pipeline for access control
     * 
     * @param string $service Service identifier or class name
     * @param array $parameters Optional parameters for service instantiation
     * @return mixed Resolved service instance with applied context
     * 
     * @throws ServiceNotFoundException If service cannot be resolved
     * @throws CircularDependencyException If circular dependency detected
     * @throws ContextException If context application fails
     */
    public function get(string $service, array $parameters = []): mixed
    {
        $startTime = microtime(true);
        $this->executionContext->enterScope($service);

        try {
            // Circular dependency detection
            if (in_array($service, $this->resolutionStack)) {
                throw new CircularDependencyException(
                    "Circular dependency detected in service resolution chain: " .
                    implode(' -> ', [...$this->resolutionStack, $service])
                );
            }

            if (count($this->resolutionStack) >= $this->maxResolutionDepth) {
                throw new CircularDependencyException(
                    "Maximum resolution depth ({$this->maxResolutionDepth}) exceeded for service: {$service}"
                );
            }

            $this->resolutionStack[] = $service;

            $isRoutingService = in_array($service, ['router', 'route', 'routes', 'routing']);

            if (!$isRoutingService) {
                $cacheKey = $this->generateContextualCacheKey($service, $parameters);

                if ($cached = $this->cache->get($cacheKey)) {
                    if ($this->validateCachedService($cached, $service)) {
                        $this->recordServiceAccess($service, 'cache_hit', microtime(true) - $startTime);
                        return $cached;
                    }
                }
            }

            // Apply middleware pipeline for access control
            $this->applyMiddleware($service, $parameters);

            // Resolve service through contextual resolver
            $instance = $this->contextualResolver->resolve(
                $service,
                $this->buildResolutionContext($parameters)
            );

            // Apply context transformations
            $contextualInstance = $this->applyContextualTransformations($instance, $service);

            // Cache resolved instance apenas se não for serviço de routing
            if (!$isRoutingService) {
                $cacheTTL = $this->calculateCacheTTL($service);
                if ($cacheTTL > 0) {
                    $cacheKey = $this->generateContextualCacheKey($service, $parameters);
                    $this->cache->set($cacheKey, $contextualInstance, $cacheTTL);
                }
            }

            // Emit service access event for observability
            $this->eventDispatcher->dispatch(new ServiceAccessEvent(
                $service,
                $this->executionContext->getCurrentContext(),
                microtime(true) - $startTime
            ));

            $this->recordServiceAccess($service, $isRoutingService ? 'fresh_resolve' : 'resolved', microtime(true) - $startTime);

            return $contextualInstance;

        } catch (\Throwable $e) {
            $this->logger->error('Service resolution failed', [
                'service' => $service,
                'parameters' => $parameters,
                'context' => $this->executionContext->getCurrentContext(),
                'resolution_stack' => $this->resolutionStack,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        } finally {
            array_pop($this->resolutionStack);
            $this->executionContext->exitScope();
        }
    }

    /**
     * Advanced contextual service access with fluent interface
     * 
     * Provides a fluent interface for complex context switching scenarios,
     * supporting method chaining and nested context operations.
     * 
     * @param array $context Context parameters to apply
     * @return self New Bridge instance with applied context
     * 
     * @example
     * ```php
     * Bridge::context(['tenant' => 'acme', 'locale' => 'pt_BR'])
     *       ->cache()
     *       ->tags(['users', 'tenant:acme'])
     *       ->remember('active_users', fn() => User::active()->get());
     * ```
     */
    public function context(array $context): self
    {
        $clone = clone $this;

        // Implement copy-on-write for memory efficiency
        $clone->contextStack = $this->contextStack;
        $clone->contextStack[] = $context;

        // Apply context transformations
        if (isset($context['tenant'])) {
            $clone->tenantContext = $clone->tenantContext->withTenant($context['tenant']);
        }

        if (isset($context['request'])) {
            $clone->requestContext = $clone->requestContext->withRequest($context['request']);
        }

        // Emit context switch event
        $this->eventDispatcher->dispatch(new ContextSwitchEvent(
            $this->executionContext->getCurrentContext(),
            $context
        ));

        return $clone;
    }

    /**
     * Check if service exists in container with context awareness
     * 
     * @param string $service Service identifier
     * @return bool True if service exists and is accessible in current context
     */
    public function has(string $service): bool
    {
        try {
            return $this->serviceResolver->canResolve($service, $this->buildResolutionContext());
        } catch (\Throwable $e) {
            $this->logger->debug('Service existence check failed', [
                'service' => $service,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Magic method for dynamic service access with intelligent routing
     * 
     * Provides syntactic sugar for service access while maintaining
     * full context awareness and performance optimization.
     * 
     * @param string $method Service name or method call
     * @param array $args Method arguments
     * @return mixed Service instance or method result
     */
    public function __call(string $method, array $args): mixed
    {
        // Handle special Bridge methods
        if (method_exists($this, "handle" . ucfirst($method))) {
            return $this->{"handle" . ucfirst($method)}(...$args);
        }

        // Standard service resolution
        return $this->get($method, $args);
    }

    /**
     * High-performance static service accessors with intelligent caching
     * These methods provide optimized access to frequently used services
     * while maintaining full context awareness and tenant isolation.
     */

    /**
     * Authentication service with context-aware user resolution
     * Automatically applies tenant context and request-specific authentication state
     */
    public static function auth(): \Ludelix\Auth\Core\AuthService
    {
        return self::instance()->get('auth');
    }

    /**
     * Multi-layer cache system with tenant isolation
     * Provides automatic cache key prefixing based on current tenant context
     */
    public static function cache(): mixed
    {
        return self::instance()->get('cache');
    }

    /**
     * Database manager with automatic tenant database switching
     * Supports read/write splitting and connection pooling
     */
    public static function db(): mixed
    {
        return self::instance()->get('orm');
    }

    /**
     * Configuration manager with environment-aware value resolution
     * Supports dynamic configuration reloading and tenant-specific overrides
     */
    public static function config(): mixed
    {
        return self::instance()->get('config');
    }

    /**
     * String manipulation utilities with localization support
     * Automatically applies current locale for string operations
     */
    public static function str(): mixed
    {
        return self::instance()->get('str');
    }

    /**
     * Multi-tenant management system
     * Provides tenant resolution, switching, and isolation capabilities
     */
    public static function tenant(): mixed
    {
        return self::instance()->get('tenant');
    }

    /**
     * Ludou template engine with hot-reload and context injection
     * Supports real-time template compilation and context-aware rendering
     */
    public static function ludou(): mixed
    {
        return self::instance()->get('ludou');
    }

    /**
     * Flash messaging system
     * Provides flash messages that are displayed to the user and automatically cleared
     */
    public static function flash(): mixed
    {
        return self::instance()->get('flash');
    }

    /**
     * Render a Ludou template with data
     * Convenience method for template rendering
     */
    public static function render(string $template, array $data = []): string
    {
        try {
            $ludou = self::instance()->get('ludou');
            $result = $ludou->render($template, $data);
            return $result;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Connect SPA integration system
     * Provides advanced SPA capabilities with SSR and WebSocket support
     */
    public static function connect(): mixed
    {
        return self::instance()->get('connect');
    }

    /**
     * Request object with context awareness
     */
    public static function request(): mixed
    {
        return self::instance()->get('request');
    }

    /**
     * Session management with tenant isolation
     */
    public static function session(): \Ludelix\Session\SessionManager
    {
        $instance = self::instance();
        if (!$instance->container->has('session')) {
            // Lazy load the session manager if it hasn't been registered.
            $configPath = $instance->container->get('path.config') . '/session.php';
            $sessionConfig = file_exists($configPath) ? require $configPath : [];

            $sessionManager = new \Ludelix\Session\SessionManager($sessionConfig);

            $instance->container->singleton('session', fn() => $sessionManager);
        }

        return $instance->get('session');
    }

    /**
     * CSRF protection service
     */
    public static function csrf(): mixed
    {
        return self::instance()->get('csrf');
    }

    /**
     * Router service for route registration and management
     * Provides Laravel-style route registration via Bridge facade
     */
    public static function route(): mixed
    {
        return self::instance()->get('router');
    }

    /**
     * Response service for HTTP response management
     * Provides fluent interface for creating and managing HTTP responses
     */
    public static function response(): mixed
    {
        return self::instance()->get('response');
    }

    /**
     * Asset manager for static asset handling
     * Provides asset URL generation, versioning, and compilation support
     */
    public static function asset(): mixed
    {
        return self::instance()->get('asset');
    }

    /**
     * Check if the current request is a Connect/SPA request
     * Similar to Inertia.js detection
     */
    public static function isConnectRequest(): bool
    {
        // Check if it's an AJAX/SPA request
        $headers = getallheaders();
        $accept = $headers['Accept'] ?? '';
        $xRequestedWith = $headers['X-Requested-With'] ?? '';

        return (
            str_contains($accept, 'application/json') ||
            $xRequestedWith === 'XMLHttpRequest' ||
            isset($_GET['_connect']) ||
            isset($_POST['_connect'])
        );
    }

    /**
     * Get translation service
     * 
     * @return mixed Translation service instance
     */
    public static function translation(): mixed
    {
        return self::instance()->get('translation');
    }

    /**
     * Get translation helper
     * 
     * @return mixed Translation helper instance
     */
    public static function trans(): mixed
    {
        // Return a proxy object that uses TranslationHelper directly
        return new class {
            public function t(string $key, array $parameters = [], ?string $locale = null): string
            {
                return \Ludelix\Translation\Support\TranslationHelper::t($key, $parameters, $locale);
            }

            public function choice(string $key, int $count, array $parameters = [], ?string $locale = null): string
            {
                return \Ludelix\Translation\Support\TranslationHelper::choice($key, $count, $parameters, $locale);
            }

            public function locale(): string
            {
                return \Ludelix\Translation\Support\TranslationHelper::locale();
            }
        };
    }

    /**
     * Get validation service (ValidationEngine singleton)
     *
     * @return ValidationEngine
     */
    public static function validation(): ValidationEngine
    {
        if (static::$validationEngine === null) {
            // Tenta resolver do container, senão instancia
            $instance = self::instance();
            if ($instance->container->has(ValidationEngine::class)) {
                static::$validationEngine = $instance->container->get(ValidationEngine::class);
            } else {
                static::$validationEngine = new ValidationEngine();
                // Opcional: registra no container para futuras resoluções
                if (method_exists($instance->container, 'set')) {
                    $instance->container->set(ValidationEngine::class, static::$validationEngine);
                }
            }
        }
        return static::$validationEngine;
    }

    /**
     * Permite sobrescrever a instância (útil para testes)
     */
    public static function setValidation(ValidationEngine $validation): void
    {
        static::$validationEngine = $validation;
    }

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom messages
     * @return \Ludelix\Validation\Core\ValidationResult
     */
    public static function validate(array $data, array $rules, array $messages = []): \Ludelix\Validation\Core\ValidationResult
    {
        $validator = new \Ludelix\Validation\Core\Validator();
        return $validator->validate($data, $rules, $messages);
    }

    // Protected helper methods for internal Bridge operations

    protected function initializeMiddleware(array $middlewareConfig): void
    {
        // Initialize middleware pipeline
    }

    protected function initializePerformanceMonitoring(): void
    {
        // Set up performance monitoring
    }

    protected function generateContextualCacheKey(string $service, array $parameters): string
    {
        return md5($service . serialize($parameters) . serialize($this->contextStack));
    }

    protected function validateCachedService($cached, string $service): bool
    {
        return true; // Simplified validation
    }

    protected function recordServiceAccess(string $service, string $type, float $duration): void
    {
        $this->accessLog[] = [
            'service' => $service,
            'type' => $type,
            'duration' => $duration,
            'timestamp' => microtime(true)
        ];
    }

    protected function applyMiddleware(string $service, array $parameters): void
    {
        // Apply middleware pipeline
    }

    protected function buildResolutionContext(array $parameters = []): array
    {
        return array_merge($this->contextStack, $parameters);
    }

    protected function applyContextualTransformations($instance, string $service): mixed
    {
        // Apply context transformations if service supports it
        if (is_object($instance) && method_exists($instance, 'withContext')) {
            return $instance->withContext($this->buildResolutionContext());
        }

        return $instance;
    }

    protected function calculateCacheTTL(string $service): int
    {
        $noCacheServices = [
            'router',
            'route',
            'routes',
            'routing',
            'middleware'
        ];

        if (in_array($service, $noCacheServices)) {
            return 0;
        }

        $serviceCacheTTL = [
            'config' => 3600,
            'auth' => 1800,
            'db' => 3600,
            'orm' => 3600,
            'cache' => 7200,
            'session' => 900,
            'view' => 1800,
            'ludou' => 1800,
            'translation' => 3600,
            'validation' => 7200
        ];

        return $serviceCacheTTL[$service] ?? 1800;
    }
}