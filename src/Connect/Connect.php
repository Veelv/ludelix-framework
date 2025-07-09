<?php

namespace Ludelix\Connect;

use Ludelix\Interface\Connect\ConnectInterface;
use Ludelix\Connect\Core\ConnectManager;
use Ludelix\Connect\Core\ComponentResolver;
use Ludelix\Connect\Core\ResponseBuilder;
use Ludelix\Connect\SSR\ServerSideRenderer;
use Ludelix\Connect\WebSocket\SyncManager;
use Ludelix\Connect\Events\ComponentRenderEvent;
use Ludelix\Connect\Events\SSRRenderEvent;
use Ludelix\Connect\Exceptions\ComponentNotFoundException;
use Ludelix\Connect\Exceptions\SSRException;
use Ludelix\Bridge\Bridge;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Core\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * LudelixConnect - Advanced SPA Integration System
 * 
 * LudelixConnect is a sophisticated replacement for Inertia.js, providing
 * superior SPA integration with advanced features including:
 * 
 * Core Features:
 * - Server-Side Rendering (SSR) with V8 engine integration
 * - Real-time WebSocket synchronization for shared state
 * - Hot module replacement for development productivity
 * - Intelligent component resolution and lazy loading
 * - Advanced caching strategies with invalidation
 * - Multi-framework support (React, Vue, Svelte)
 * - Progressive enhancement capabilities
 * - SEO optimization with meta tag injection
 * 
 * Architecture:
 * The Connect system operates through multiple sophisticated layers:
 * 
 * 1. Request Detection Layer:
 *    - Identifies Connect requests via headers
 *    - Handles both initial page loads and AJAX navigation
 *    - Manages request lifecycle and state preservation
 * 
 * 2. Component Resolution Layer:
 *    - Resolves component names to actual implementations
 *    - Handles component lazy loading and code splitting
 *    - Manages component dependency injection
 * 
 * 3. Server-Side Rendering Layer:
 *    - Pre-renders components on server for SEO
 *    - Hydrates client-side for interactivity
 *    - Manages SSR cache and invalidation
 * 
 * 4. State Synchronization Layer:
 *    - Synchronizes shared state via WebSockets
 *    - Handles optimistic updates and conflict resolution
 *    - Manages real-time collaborative features
 * 
 * 5. Response Building Layer:
 *    - Constructs appropriate responses for different request types
 *    - Handles JSON responses for AJAX requests
 *    - Manages HTML responses for initial page loads
 * 
 * Performance Optimizations:
 * - Component-level caching with smart invalidation
 * - Predictive preloading of likely next components
 * - Efficient diff algorithms for state updates
 * - Memory-efficient state management
 * - Optimized bundle splitting strategies
 * 
 * @package Ludelix\Connect
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 * 
 * @example Basic Usage:
 * ```php
 * // Simple component rendering
 * return Connect::component('Dashboard', ['user' => $user]);
 * 
 * // Advanced usage with SSR and WebSocket sync
 * return Connect::ssr()
 *              ->websocket(['room' => 'dashboard'])
 *              ->share(['appName' => 'MyApp'])
 *              ->component('Dashboard', ['user' => $user]);
 * 
 * // Static factory methods
 * Connect::share(['theme' => 'dark']);
 * Connect::version('v1.2.3');
 * ```
 */
class Connect implements ConnectInterface
{
    /**
     * Singleton instance for global state management
     */
    protected static ?self $instance = null;
    
    /**
     * Core system dependencies
     */
    protected ConnectManager $manager;
    protected ComponentResolver $componentResolver;
    protected ResponseBuilder $responseBuilder;
    protected ServerSideRenderer $ssrRenderer;
    protected SyncManager $syncManager;
    protected EventDispatcher $eventDispatcher;
    protected LoggerInterface $logger;
    
    /**
     * Current request context
     */
    protected Request $request;
    protected array $sharedProps = [];
    protected array $config = [];
    
    /**
     * Rendering configuration
     */
    protected bool $ssrEnabled = false;
    protected string $rootTemplate = 'app';
    protected array $websocketConfig = [];
    protected string $version = '';
    
    /**
     * Performance and debugging
     */
    protected array $renderMetrics = [];
    protected bool $debugMode = false;
    protected array $componentCache = [];
    
    /**
     * Initialize Connect with comprehensive dependency injection
     * 
     * @param ConnectManager $manager Core Connect management
     * @param ComponentResolver $componentResolver Component resolution system
     * @param ResponseBuilder $responseBuilder Response construction
     * @param ServerSideRenderer $ssrRenderer SSR capabilities
     * @param SyncManager $syncManager WebSocket synchronization
     * @param EventDispatcher $eventDispatcher Event system
     * @param LoggerInterface $logger Structured logging
     * @param Request $request Current HTTP request
     * @param array $config Connect configuration
     */
    public function __construct(
        ConnectManager $manager,
        ComponentResolver $componentResolver,
        ResponseBuilder $responseBuilder,
        ServerSideRenderer $ssrRenderer,
        SyncManager $syncManager,
        EventDispatcher $eventDispatcher,
        LoggerInterface $logger,
        Request $request,
        array $config = []
    ) {
        $this->manager = $manager;
        $this->componentResolver = $componentResolver;
        $this->responseBuilder = $responseBuilder;
        $this->ssrRenderer = $ssrRenderer;
        $this->syncManager = $syncManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->request = $request;
        $this->config = $config;
        
        // Initialize configuration
        $this->ssrEnabled = $config['ssr']['enabled'] ?? false;
        $this->rootTemplate = $config['root_template'] ?? 'app';
        $this->websocketConfig = $config['websocket'] ?? [];
        $this->debugMode = $config['debug'] ?? false;
        
        // Generate version hash for cache busting
        $this->version = $this->generateVersion();
        
        // Initialize shared props with system defaults
        $this->initializeSharedProps();
        
        $this->logger->info('LudelixConnect initialized', [
            'ssr_enabled' => $this->ssrEnabled,
            'websocket_enabled' => !empty($this->websocketConfig),
            'debug_mode' => $this->debugMode,
            'version' => $this->version
        ]);
    }
    
    /**
     * Render a component with comprehensive SPA integration
     * 
     * This method orchestrates the complete component rendering pipeline:
     * 1. Resolves the component and validates its existence
     * 2. Merges component props with shared application state
     * 3. Applies Server-Side Rendering if enabled
     * 4. Configures WebSocket synchronization for real-time updates
     * 5. Builds appropriate response based on request type
     * 6. Emits events for observability and debugging
     * 
     * @param string $component Component name to render
     * @param array $props Component-specific properties
     * @param array $shared Additional shared state (merged with global shared)
     * @return Response Appropriate response for request type
     * 
     * @throws ComponentNotFoundException If component cannot be resolved
     * @throws SSRException If SSR fails and fallback is disabled
     */
    public function component(string $component, array $props = [], array $shared = []): Response
    {
        $startTime = microtime(true);
        
        try {
            // Validate and resolve component
            if (!$this->componentResolver->exists($component)) {
                throw new ComponentNotFoundException(
                    "Component '{$component}' not found in any registered resolver paths"
                );
            }
            
            // Merge all props with proper precedence
            $mergedShared = array_merge($this->sharedProps, $shared);
            $finalProps = $this->buildComponentProps($props, $mergedShared);
            
            // Emit pre-render event for middleware and plugins
            $this->eventDispatcher->dispatch(new ComponentRenderEvent(
                $component,
                $finalProps,
                $this->isConnectRequest(),
                $this->ssrEnabled
            ));
            
            // Handle Server-Side Rendering if enabled
            $ssrContent = null;
            if ($this->ssrEnabled && $this->shouldRenderSSR($component)) {
                try {
                    $ssrContent = $this->ssrRenderer->render($component, $finalProps);
                    
                    $this->eventDispatcher->dispatch(new SSRRenderEvent(
                        $component,
                        $finalProps,
                        strlen($ssrContent),
                        microtime(true) - $startTime
                    ));
                } catch (\Throwable $e) {
                    $this->logger->warning('SSR rendering failed, falling back to client-side', [
                        'component' => $component,
                        'error' => $e->getMessage(),
                        'fallback_enabled' => $this->config['ssr']['fallback'] ?? true
                    ]);
                    
                    if (!($this->config['ssr']['fallback'] ?? true)) {
                        throw new SSRException(
                            "SSR failed for component '{$component}' and fallback is disabled",
                            0,
                            $e
                        );
                    }
                }
            }
            
            // Configure WebSocket synchronization
            $websocketData = null;
            if (!empty($this->websocketConfig)) {
                $websocketData = $this->syncManager->prepareSync(
                    $component,
                    $finalProps,
                    $this->websocketConfig
                );
            }
            
            // Build response based on request type
            $response = $this->responseBuilder->build([
                'component' => $component,
                'props' => $finalProps,
                'url' => $this->request->getUri(),
                'version' => $this->version,
                'ssr_content' => $ssrContent,
                'websocket' => $websocketData,
                'is_connect_request' => $this->isConnectRequest(),
                'root_template' => $this->rootTemplate
            ]);
            
            // Record performance metrics
            $this->recordRenderMetrics($component, microtime(true) - $startTime);
            
            return $response;
            
        } catch (\Throwable $e) {
            $this->logger->error('Component rendering failed', [
                'component' => $component,
                'props_keys' => array_keys($props),
                'shared_keys' => array_keys($shared),
                'is_connect_request' => $this->isConnectRequest(),
                'error' => $e->getMessage(),
                'trace' => $this->debugMode ? $e->getTraceAsString() : null
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Set shared properties with intelligent merging
     * 
     * @param array $shared Shared state data
     * @return ConnectInterface Fluent interface
     */
    public function share(array $shared): ConnectInterface
    {
        $this->sharedProps = array_merge($this->sharedProps, $shared);
        
        // Sync with WebSocket if configured
        if (!empty($this->websocketConfig)) {
            $this->syncManager->syncSharedState($this->sharedProps);
        }
        
        return $this;
    }
    
    /**
     * Enable/disable Server-Side Rendering
     * 
     * @param bool $enabled SSR enabled state
     * @return ConnectInterface Fluent interface
     */
    public function ssr(bool $enabled = true): ConnectInterface
    {
        $this->ssrEnabled = $enabled;
        return $this;
    }
    
    /**
     * Set root template for SPA mounting
     * 
     * @param string $template Template name
     * @return ConnectInterface Fluent interface
     */
    public function rootTemplate(string $template): ConnectInterface
    {
        $this->rootTemplate = $template;
        return $this;
    }
    
    /**
     * Configure WebSocket synchronization
     * 
     * @param array $config WebSocket configuration
     * @return ConnectInterface Fluent interface
     */
    public function websocket(array $config = []): ConnectInterface
    {
        $this->websocketConfig = array_merge($this->websocketConfig, $config);
        return $this;
    }
    
    /**
     * Check if current request is from LudelixConnect
     * 
     * @return bool True if Connect request
     */
    public function isConnectRequest(): bool
    {
        return $this->request->hasHeader('X-Ludelix-Connect') ||
               $this->request->hasHeader('X-Requested-With') && 
               $this->request->getHeader('X-Requested-With') === 'LudelixConnect';
    }
    
    /**
     * Get current page version for cache busting
     * 
     * @return string Version hash
     */
    public function getVersion(): string
    {
        return $this->version;
    }
    
    /**
     * Static factory methods for global access and ludelix-connect compatibility
     */
    
    /**
     * Render component (ludelix-connect compatibility)
     */
    public static function render(string $component, array $props = [], array $shared = []): Response
    {
        return self::instance()->component($component, $props, $shared);
    }
    
    /**
     * Set shared props statically (ludelix-connect compatibility)
     */
    public static function shareGlobal(array|string $key, mixed $value = null): void
    {
        $instance = self::instance();
        
        if (is_array($key)) {
            $instance->share($key);
        } else {
            $instance->share([$key => $value]);
        }
    }
    
    /**
     * Set version statically (ludelix-connect compatibility)
     */
    public static function version(string $version): void
    {
        self::instance()->version = $version;
    }
    
    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = Bridge::connect();
        }
        
        return self::$instance;
    }
    
    // Protected helper methods
    
    protected function initializeSharedProps(): void
    {
        $this->sharedProps = [
            'app' => [
                'name' => Bridge::config()->get('app.name', 'Ludelix'),
                'version' => $this->version,
                'locale' => Bridge::config()->get('app.locale', 'en'),
                'debug' => $this->debugMode,
            ],
            'auth' => [
                'user' => Bridge::auth()->user(),
                'authenticated' => Bridge::auth()->check(),
            ],
            'csrf' => [
                'token' => Bridge::csrf()->token(),
            ],
        ];
    }
    
    protected function buildComponentProps(array $props, array $shared): array
    {
        return [
            'props' => $props,
            'shared' => $shared,
            'meta' => [
                'component' => true,
                'version' => $this->version,
                'timestamp' => time(),
            ]
        ];
    }
    
    protected function shouldRenderSSR(string $component): bool
    {
        // Skip SSR for Connect requests (already client-side)
        if ($this->isConnectRequest()) {
            return false;
        }
        
        // Check component-specific SSR configuration
        return $this->componentResolver->supportsSSR($component);
    }
    
    protected function generateVersion(): string
    {
        return md5(
            Bridge::config()->get('app.key', 'ludelix') . 
            filemtime(__FILE__)
        );
    }
    
    protected function recordRenderMetrics(string $component, float $duration): void
    {
        $this->renderMetrics[] = [
            'component' => $component,
            'duration' => $duration,
            'memory' => memory_get_usage(true),
            'timestamp' => microtime(true),
            'ssr_used' => $this->ssrEnabled,
            'websocket_used' => !empty($this->websocketConfig),
        ];
    }
}