<?php

namespace Ludelix\Interface\Routing;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Router Interface - Advanced Dynamic Routing System Contract
 * 
 * Defines the comprehensive contract for Ludelix's next-generation routing system
 * that unifies HTTP, WebSocket, and GraphQL routing in a single, cohesive interface.
 * This system represents a paradigm shift from traditional routing approaches,
 * providing enterprise-grade features with unprecedented flexibility.
 * 
 * Core Architectural Principles:
 * 
 * 1. **Protocol Unification**:
 *    - HTTP REST API routing with full RESTful semantics
 *    - WebSocket real-time communication routing
 *    - GraphQL query and mutation routing
 *    - Server-Sent Events (SSE) routing
 *    - gRPC service routing (future extension)
 * 
 * 2. **Multi-Format Route Definition**:
 *    - YAML-based declarative routing for configuration management
 *    - PHP-based programmatic routing for dynamic scenarios
 *    - JSON-based routing for API-driven route management
 *    - Database-driven routing for multi-tenant scenarios
 *    - Environment-specific routing configurations
 * 
 * 3. **Advanced Route Resolution**:
 *    - Multi-dimensional route matching (method, path, headers, content-type)
 *    - Contextual route resolution with tenant awareness
 *    - Dynamic route compilation with intelligent caching
 *    - Route parameter binding with type coercion
 *    - Conditional routing based on runtime conditions
 * 
 * 4. **Enterprise-Grade Features**:
 *    - Hierarchical middleware pipeline with dependency injection
 *    - Rate limiting with tenant-specific quotas
 *    - API versioning with backward compatibility
 *    - Route-level caching with intelligent invalidation
 *    - Performance monitoring and analytics integration
 * 
 * 5. **Security & Compliance**:
 *    - Route-level authentication and authorization
 *    - CORS handling with fine-grained control
 *    - CSRF protection with token validation
 *    - Input validation and sanitization
 *    - Audit logging for compliance requirements
 * 
 * Advanced Routing Features:
 * 
 * - **Smart Route Compilation**: Routes are compiled into optimized PHP code
 *   for maximum performance, with automatic recompilation on changes
 * 
 * - **Contextual Route Groups**: Routes can be grouped by tenant, API version,
 *   feature flags, or custom criteria with inherited configurations
 * 
 * - **Dynamic Route Registration**: Routes can be registered at runtime
 *   based on database configuration, plugin systems, or external APIs
 * 
 * - **Route Model Binding**: Automatic model resolution and injection
 *   with support for custom resolution strategies and caching
 * 
 * - **Subdomain Routing**: Full support for subdomain-based routing
 *   with tenant isolation and custom domain handling
 * 
 * - **Route Caching Strategies**: Multiple caching layers including
 *   route compilation cache, resolution cache, and response cache
 * 
 * @package Ludelix\Interface\Routing
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 * 
 * @example Basic Usage:
 * ```php
 * // Register HTTP routes
 * $router->get('/users', UserController::class . '@index');
 * $router->post('/users', UserController::class . '@store');
 * 
 * // Register WebSocket routes
 * $router->websocket('/chat', ChatHandler::class);
 * 
 * // Register GraphQL routes
 * $router->graphql('/graphql', GraphQLHandler::class);
 * ```
 * 
 * @example Advanced Usage:
 * ```php
 * // Route groups with middleware and prefixes
 * $router->group(['prefix' => 'api/v1', 'middleware' => ['auth', 'throttle']], function($router) {
 *     $router->resource('users', UserController::class);
 *     $router->apiResource('posts', PostController::class);
 * });
 * 
 * // Tenant-aware routing
 * $router->tenant(['domain' => '{tenant}.example.com'], function($router) {
 *     $router->get('/dashboard', DashboardController::class);
 * });
 * 
 * // Conditional routing
 * $router->when(['feature' => 'beta_features'], function($router) {
 *     $router->get('/beta', BetaController::class);
 * });
 * ```
 */
interface RouterInterface
{
    /**
     * Register a GET route with comprehensive configuration options
     * 
     * @param string $path Route path pattern with parameter placeholders
     * @param mixed $handler Route handler (controller, closure, or callable)
     * @param array $options Additional route configuration options
     * @return RouteInterface Configured route instance for method chaining
     */
    public function get(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a POST route with validation and security features
     * 
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration including validation rules
     * @return RouteInterface Configured route instance
     */
    public function post(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a PUT route for resource updates
     * 
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration
     * @return RouteInterface Configured route instance
     */
    public function put(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a PATCH route for partial resource updates
     * 
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration
     * @return RouteInterface Configured route instance
     */
    public function patch(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a DELETE route for resource removal
     * 
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration
     * @return RouteInterface Configured route instance
     */
    public function delete(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a route that responds to multiple HTTP methods
     * 
     * @param array $methods HTTP methods to match
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration
     * @return RouteInterface Configured route instance
     */
    public function match(array $methods, string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a route that responds to any HTTP method
     * 
     * @param string $path Route path pattern
     * @param mixed $handler Route handler
     * @param array $options Route configuration
     * @return RouteInterface Configured route instance
     */
    public function any(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a WebSocket route for real-time communication
     * 
     * @param string $path WebSocket endpoint path
     * @param mixed $handler WebSocket handler class or callable
     * @param array $options WebSocket-specific configuration
     * @return RouteInterface Configured WebSocket route
     */
    public function websocket(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a GraphQL endpoint with schema configuration
     * 
     * @param string $path GraphQL endpoint path
     * @param mixed $handler GraphQL handler or schema
     * @param array $options GraphQL-specific configuration
     * @return RouteInterface Configured GraphQL route
     */
    public function graphql(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Register a Server-Sent Events (SSE) endpoint
     * 
     * @param string $path SSE endpoint path
     * @param mixed $handler SSE handler
     * @param array $options SSE-specific configuration
     * @return RouteInterface Configured SSE route
     */
    public function sse(string $path, mixed $handler, array $options = []): RouteInterface;
    
    /**
     * Create a RESTful resource route collection
     * 
     * Automatically generates routes for index, show, create, store, edit, update, destroy
     * 
     * @param string $name Resource name (used for route naming)
     * @param string $controller Controller class name
     * @param array $options Resource configuration options
     * @return RouteGroupInterface Resource route group
     */
    public function resource(string $name, string $controller, array $options = []): RouteGroupInterface;
    
    /**
     * Create an API resource route collection (without create/edit forms)
     * 
     * @param string $name Resource name
     * @param string $controller Controller class name
     * @param array $options API resource configuration
     * @return RouteGroupInterface API resource route group
     */
    public function apiResource(string $name, string $controller, array $options = []): RouteGroupInterface;
    
    /**
     * Create a route group with shared attributes
     * 
     * @param array $attributes Shared group attributes (prefix, middleware, namespace, etc.)
     * @param callable $callback Callback to register routes within the group
     * @return RouteGroupInterface Route group instance
     */
    public function group(array $attributes, callable $callback): RouteGroupInterface;
    
    /**
     * Create a tenant-aware route group
     * 
     * @param array $tenantConfig Tenant configuration (domain patterns, etc.)
     * @param callable $callback Callback to register tenant routes
     * @return RouteGroupInterface Tenant route group
     */
    public function tenant(array $tenantConfig, callable $callback): RouteGroupInterface;
    
    /**
     * Create a versioned API route group
     * 
     * @param string $version API version identifier
     * @param callable $callback Callback to register versioned routes
     * @param array $options Version-specific options
     * @return RouteGroupInterface Versioned route group
     */
    public function version(string $version, callable $callback, array $options = []): RouteGroupInterface;
    
    /**
     * Create conditional routes based on feature flags or runtime conditions
     * 
     * @param array $conditions Conditions that must be met for routes to be active
     * @param callable $callback Callback to register conditional routes
     * @return RouteGroupInterface Conditional route group
     */
    public function when(array $conditions, callable $callback): RouteGroupInterface;
    
    /**
     * Load routes from YAML configuration file
     * 
     * @param string $filePath Path to YAML route configuration file
     * @param array $options Loading options and context
     * @return self Router instance for method chaining
     */
    public function loadFromYaml(string $filePath, array $options = []): self;
    
    /**
     * Load routes from PHP configuration file
     * 
     * @param string $filePath Path to PHP route configuration file
     * @param array $options Loading options and context
     * @return self Router instance for method chaining
     */
    public function loadFromPhp(string $filePath, array $options = []): self;
    
    /**
     * Load routes from JSON configuration
     * 
     * @param string $json JSON route configuration string
     * @param array $options Parsing options
     * @return self Router instance for method chaining
     */
    public function loadFromJson(string $json, array $options = []): self;
    
    /**
     * Load routes from database configuration
     * 
     * @param array $criteria Database query criteria for route loading
     * @param array $options Database loading options
     * @return self Router instance for method chaining
     */
    public function loadFromDatabase(array $criteria = [], array $options = []): self;
    
    /**
     * Dispatch incoming request to appropriate route handler
     * 
     * @param Request $request Incoming HTTP request
     * @return Response Route handler response
     * @throws RouteNotFoundException If no matching route is found
     * @throws MethodNotAllowedException If route exists but method not allowed
     */
    public function dispatch(Request $request): Response;
    
    /**
     * Resolve route information for given request without dispatching
     * 
     * @param Request $request Request to resolve
     * @return array Route resolution information
     */
    public function resolve(Request $request): array;
    
    /**
     * Generate URL for named route with parameters
     * 
     * @param string $name Route name
     * @param array $parameters Route parameters
     * @param array $options URL generation options
     * @return string Generated URL
     */
    public function url(string $name, array $parameters = [], array $options = []): string;
    
    /**
     * Check if named route exists
     * 
     * @param string $name Route name to check
     * @return bool True if route exists
     */
    public function hasRoute(string $name): bool;
    
    /**
     * Get route by name
     * 
     * @param string $name Route name
     * @return RouteInterface|null Route instance or null if not found
     */
    public function getRoute(string $name): ?RouteInterface;
    
    /**
     * Get all registered routes
     * 
     * @param array $filters Optional filters to apply
     * @return array Collection of registered routes
     */
    public function getRoutes(array $filters = []): array;
    
    /**
     * Compile routes for optimal performance
     * 
     * @param array $options Compilation options
     * @return bool True if compilation successful
     */
    public function compile(array $options = []): bool;
    
    /**
     * Clear route cache and force recompilation
     * 
     * @return bool True if cache cleared successfully
     */
    public function clearCache(): bool;
    
    /**
     * Get routing performance metrics
     * 
     * @return array Performance metrics and statistics
     */
    public function getMetrics(): array;
    
    /**
     * Enable or disable route caching
     * 
     * @param bool $enabled Cache enabled state
     * @return self Router instance for method chaining
     */
    public function setCaching(bool $enabled): self;
    
    /**
     * Set global middleware for all routes
     * 
     * @param array $middleware Middleware classes or callables
     * @return self Router instance for method chaining
     */
    public function middleware(array $middleware): self;
    
    /**
     * Set global route prefix
     * 
     * @param string $prefix URL prefix for all routes
     * @return self Router instance for method chaining
     */
    public function prefix(string $prefix): self;
    
    /**
     * Set global namespace for route handlers
     * 
     * @param string $namespace PHP namespace for controllers
     * @return self Router instance for method chaining
     */
    public function namespace(string $namespace): self;
    
    /**
     * Set domain constraint for routes
     * 
     * @param string $domain Domain pattern for route matching
     * @return self Router instance for method chaining
     */
    public function domain(string $domain): self;
    
    /**
     * Register custom route parameter pattern
     * 
     * @param string $name Parameter name
     * @param string $pattern Regular expression pattern
     * @return self Router instance for method chaining
     */
    public function pattern(string $name, string $pattern): self;
    
    /**
     * Register route model binding
     * 
     * @param string $key Parameter key
     * @param string $model Model class name
     * @param callable|null $resolver Custom resolution callback
     * @return self Router instance for method chaining
     */
    public function model(string $key, string $model, ?callable $resolver = null): self;
}