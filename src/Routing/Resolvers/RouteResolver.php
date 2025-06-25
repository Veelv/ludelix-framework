<?php

namespace Ludelix\Routing\Resolvers;

use Ludelix\Interface\Routing\RouteInterface;
use Ludelix\Routing\Core\RouteCollection;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;
use Ludelix\Core\Container;
use Ludelix\Core\Logger;

/**
 * Route Resolver - Advanced Route Resolution Engine
 * 
 * High-performance route resolution system with intelligent matching,
 * parameter extraction, and handler execution capabilities.
 * 
 * @package Ludelix\Routing\Resolvers
 * @author Ludelix Framework Team
 * @version 2.0.0
 */
class RouteResolver
{
    protected Container $container;
    protected Logger $logger;
    protected array $config;

    public function __construct(Container $container, Logger $logger, array $config = [])
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function resolveRoute(string $method, string $path, RouteCollection $routes): array
    {
        $methodRoutes = $routes->getByMethod($method);
        $allowedMethods = [];

        foreach ($routes->all() as $route) {
            $routeMethods = $route->getMethods();
            
            // Check if path matches
            if (preg_match($route->getCompiledRegex(), $path, $matches)) {
                // If method matches, we found our route
                if (in_array($method, $routeMethods)) {
                    $parameters = $this->extractParameters($route, $matches);
                    
                    return [
                        'status' => 'found',
                        'route' => $route,
                        'parameters' => $parameters,
                        'handler' => $route->getHandler()
                    ];
                }
                
                // Path matches but method doesn't - collect allowed methods
                $allowedMethods = array_merge($allowedMethods, $routeMethods);
            }
        }

        // If we have allowed methods, it's a method not allowed error
        if (!empty($allowedMethods)) {
            return [
                'status' => 'method_not_allowed',
                'allowed_methods' => array_unique($allowedMethods)
            ];
        }

        // No route found
        return [
            'status' => 'not_found'
        ];
    }

    public function resolve(RouteInterface $route, Request $request, array $parameters): Response
    {
        $handler = $route->getHandler();
        
        // Apply middleware pipeline
        $response = $this->executeMiddleware($route, $request, function() use ($handler, $request, $parameters) {
            return $this->executeHandler($handler, $request, $parameters);
        });

        return $response;
    }

    protected function extractParameters(RouteInterface $route, array $matches): array
    {
        $parameters = [];
        $parameterNames = $route->getParameterNames();
        
        // Skip the full match (index 0)
        array_shift($matches);
        
        foreach ($parameterNames as $index => $name) {
            if (isset($matches[$index])) {
                $parameters[$name] = $matches[$index];
            }
        }

        return $parameters;
    }

    protected function executeHandler(mixed $handler, Request $request, array $parameters): Response
    {
        // Handle different types of handlers
        if (is_string($handler)) {
            return $this->executeStringHandler($handler, $request, $parameters);
        }
        
        if (is_callable($handler)) {
            return $this->executeCallableHandler($handler, $request, $parameters);
        }
        
        if (is_array($handler)) {
            return $this->executeArrayHandler($handler, $request, $parameters);
        }

        throw new \InvalidArgumentException('Invalid route handler type');
    }

    protected function executeStringHandler(string $handler, Request $request, array $parameters): Response
    {
        // Parse Controller@method format
        if (str_contains($handler, '@')) {
            [$controller, $method] = explode('@', $handler, 2);
            
            $controllerInstance = $this->container->make($controller);
            $result = $controllerInstance->$method($request, ...$parameters);
            
            return $this->normalizeResponse($result);
        }

        // Single controller class - assume __invoke method
        $controllerInstance = $this->container->make($handler);
        $result = $controllerInstance($request, ...$parameters);
        
        return $this->normalizeResponse($result);
    }

    protected function executeCallableHandler(callable $handler, Request $request, array $parameters): Response
    {
        $result = $handler($request, ...$parameters);
        return $this->normalizeResponse($result);
    }

    protected function executeArrayHandler(array $handler, Request $request, array $parameters): Response
    {
        [$controller, $method] = $handler;
        
        if (is_string($controller)) {
            $controller = $this->container->make($controller);
        }
        
        $result = $controller->$method($request, ...$parameters);
        return $this->normalizeResponse($result);
    }

    protected function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return new Response(json_encode($result), 200, ['Content-Type' => 'application/json']);
        }

        return new Response((string) $result);
    }

    protected function executeMiddleware(RouteInterface $route, Request $request, callable $next): Response
    {
        $middleware = $route->getMiddleware();
        
        if (empty($middleware)) {
            return $next();
        }

        return $this->runMiddlewarePipeline($middleware, $request, $next);
    }

    protected function runMiddlewarePipeline(array $middleware, Request $request, callable $destination): Response
    {
        $pipeline = array_reverse($middleware);
        
        $next = $destination;
        
        foreach ($pipeline as $middlewareName) {
            $next = function() use ($middlewareName, $request, $next) {
                $middlewareInstance = $this->resolveMiddleware($middlewareName);
                return $middlewareInstance->handle($request, $next);
            };
        }

        return $next();
    }

    protected function resolveMiddleware(string $middleware): object
    {
        // Handle middleware with parameters (e.g., "throttle:60,1")
        if (str_contains($middleware, ':')) {
            [$name, $params] = explode(':', $middleware, 2);
            $parameters = explode(',', $params);
            
            $middlewareClass = $this->getMiddlewareClass($name);
            return new $middlewareClass(...$parameters);
        }

        $middlewareClass = $this->getMiddlewareClass($middleware);
        return $this->container->make($middlewareClass);
    }

    protected function getMiddlewareClass(string $name): string
    {
        $middlewareMap = $this->config['middleware'] ?? [];
        
        return $middlewareMap[$name] ?? $name;
    }
}