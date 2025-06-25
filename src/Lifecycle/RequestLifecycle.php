<?php

namespace Ludelix\Lifecycle;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
// use Ludelix\Routing\Router;
use Ludelix\Core\Container;
use Ludelix\Lifecycle\Events\BeforeBootstrap;
use Ludelix\Lifecycle\Events\BeforeTermination;

/**
 * Request Lifecycle Manager
 * 
 * Manages the complete HTTP request lifecycle from bootstrap to termination
 */
class RequestLifecycle
{
    protected Container $container;
    protected $router;
    protected array $middlewares = [];

    public function __construct(Container $container, $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    /**
     * Handle HTTP request lifecycle
     */
    public function handle(Request $request): Response
    {
        // Bootstrap phase
        $this->bootstrap($request);
        
        // Route resolution
        $route = $this->router->match($request);
        
        // Middleware execution
        $response = $this->executeMiddlewares($request, $route);
        
        // Controller execution
        if (!$response) {
            $response = $this->executeController($request, $route);
        }
        
        // Response preparation
        return $this->prepareResponse($response);
    }

    /**
     * Bootstrap application
     */
    protected function bootstrap(Request $request): void
    {
        $event = new BeforeBootstrap($request);
        $this->container->get('events')->dispatch($event);
        
        // Load configuration
        $this->loadConfiguration();
        
        // Register services
        $this->registerServices();
        
        // Initialize components
        $this->initializeComponents();
    }

    /**
     * Execute middlewares
     */
    protected function executeMiddlewares(Request $request, $route): ?Response
    {
        $middlewares = $route->getMiddlewares() ?? [];
        
        foreach ($middlewares as $middleware) {
            $instance = $this->container->make($middleware);
            $response = $instance->handle($request);
            
            if ($response instanceof Response) {
                return $response;
            }
        }
        
        return null;
    }

    /**
     * Execute controller
     */
    protected function executeController(Request $request, $route): Response
    {
        $controller = $this->container->make($route->getController());
        $method = $route->getMethod();
        $parameters = $route->getParameters();
        
        $result = $controller->$method($request, ...$parameters);
        
        if ($result instanceof Response) {
            return $result;
        }
        
        return new Response($result);
    }

    /**
     * Prepare final response
     */
    protected function prepareResponse($response): Response
    {
        if (!$response instanceof Response) {
            $response = new Response($response);
        }
        
        // Add framework headers
        $response->setHeader('X-Powered-By', 'Ludelix Framework');
        $response->setHeader('X-Framework-Version', '1.0.0');
        
        return $response;
    }

    /**
     * Terminate request lifecycle
     */
    public function terminate(Request $request, Response $response): void
    {
        $event = new BeforeTermination($request, $response);
        $this->container->get('events')->dispatch($event);
        
        // Cleanup resources
        $this->cleanup();
        
        // Log request
        $this->logRequest($request, $response);
    }

    /**
     * Load configuration
     */
    protected function loadConfiguration(): void
    {
        // Configuration loading logic
    }

    /**
     * Register services
     */
    protected function registerServices(): void
    {
        // Service registration logic
    }

    /**
     * Initialize components
     */
    protected function initializeComponents(): void
    {
        // Component initialization logic
    }

    /**
     * Cleanup resources
     */
    protected function cleanup(): void
    {
        // Cleanup logic
    }

    /**
     * Log request
     */
    protected function logRequest(Request $request, Response $response): void
    {
        // Request logging logic
    }
}