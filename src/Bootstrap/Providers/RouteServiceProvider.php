<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Routing\Core\Router;
use Ludelix\Interface\Logging\LoggerInterface;
use Ludelix\Core\Logging\NullLogger;

class RouteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('router', function ($container) {
            // Create all required dependencies
            $routes = new \Ludelix\Routing\Core\RouteCollection();
            $logger = null;
            try {
                $logger = \Ludelix\Bridge\Bridge::instance($container)->get('logger');
            } catch (\Throwable $e) {}
            if (!$logger) {
                $logger = new NullLogger();
            }
            $compiler = new \Ludelix\Routing\Compilers\RouteCompiler($logger);
            
            // Get cache manager from container
            $cacheManager = $container->get('cache');
            $cache = new \Ludelix\Routing\Cache\RouteCache($cacheManager);
            
            $resolver = new \Ludelix\Routing\Resolvers\RouteResolver($container, $logger);
            $urlGenerator = new \Ludelix\Routing\Generators\UrlGenerator($routes);
            $eventDispatcher = new \Ludelix\Core\EventDispatcher();
            
            // Create a simple mock tenant manager for now
            $tenantManager = $this->createMockTenantManager($container);
            
            return new Router(
                $routes,
                $compiler,
                $cache,
                $resolver,
                $urlGenerator,
                $eventDispatcher,
                $logger,
                $tenantManager
            );
        });
    }

    public function boot(): void
    {
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $app = $this->container->get('app');
        $configPath = $app->basePath() . '/config/routes.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            $routeFiles = $config['files'] ?? [];
            foreach ($routeFiles as $name => $path) {
                $file = $app->basePath() . '/' . $path;
                if (file_exists($file)) {
                    $this->loadRouteFile($file, $name);
                }
            }
        }
    }

    protected function loadRouteFile(string $file, string $type): void
    {
        $middlewareGroup = match($type) {
            'api', 'api_yaml' => 'api',
            default => 'web'
        };
        if (str_ends_with($file, '.yaml') || str_ends_with($file, '.yml')) {
            $this->loadYamlRoutes($file, $middlewareGroup);
        } else {
            $this->loadPhpRoutes($file, $middlewareGroup);
        }
    }

    protected function loadPhpRoutes(string $file, string $middlewareGroup): void
    {
        require $file;
    }

    protected function loadYamlRoutes(string $file, string $middlewareGroup): void
    {
        if (!function_exists('yaml_parse_file')) {
            return;
        }

        $routes = yaml_parse_file($file);
        if (!$routes || !$this->container->has('router')) {
            return;
        }

        $router = $this->container->get('router');
        
        foreach ($routes as $path => $config) {
            $method = $config['method'] ?? 'GET';
            $action = $config['action'] ?? $config['controller'] ?? null;
            
            if ($action && method_exists($router, 'addRoute')) {
                $router->addRoute($method, $path, $action);
            }
        }
    }

    protected function createMockTenantManager($container)
    {
        // Try to create real TenantManager if available
        if (class_exists('\Ludelix\Tenant\Core\TenantManager')) {
            try {
                                 // Create required dependencies for TenantManager
                 $resolver = new \Ludelix\Tenant\Resolution\TenantResolver();
                 $dbIsolation = new \Ludelix\Tenant\Isolation\DatabaseIsolation(
                     new \Ludelix\Database\Core\ConnectionManager([
                         'default' => 'sqlite',
                         'connections' => [
                             'sqlite' => [
                                 'driver' => 'sqlite',
                                 'database' => ':memory:'
                             ]
                         ]
                     ])
                 );
                 $cacheIsolation = new \Ludelix\Tenant\Isolation\CacheIsolation();
                 $guard = new \Ludelix\Tenant\Security\TenantGuard();
                 $metrics = new \Ludelix\Tenant\Analytics\TenantMetrics();
                
                return \Ludelix\Tenant\Core\TenantManager::initialize([
                    $resolver,
                    $dbIsolation,
                    $cacheIsolation,
                    $guard,
                    $metrics
                ]);
            } catch (\Throwable $e) {
                // Fall back to mock if initialization fails
            }
        }
        
        // Create a simple mock tenant manager that implements the required interface
        return new class($container) {
            protected $container;
            
            public function __construct($container) {
                $this->container = $container;
            }
            
            public function resolve($request, $options = []) {
                return null; // No tenant for now
            }
            
            public function current() {
                return null; // No current tenant
            }
            
            public function hasTenant() {
                return false; // No tenant active
            }
            
            public function switch($tenant, $options = []) {
                return $this;
            }
            
            public function withTenant($tenant, $callback) {
                return $callback();
            }
            
            public function pop() {
                return $this;
            }
            
            public function database() {
                return null;
            }
            
            public function cache() {
                return null;
            }
            
            public function guard() {
                return null;
            }
            
            public function metrics() {
                return null;
            }
            
            public function clearCache() {
                return $this;
            }
        };
    }
}