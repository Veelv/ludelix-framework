<?php

namespace Ludelix\Routing\Parsers;

use Ludelix\Interface\Routing\RouterInterface;
use Ludelix\Routing\Exceptions\RouteParsingException;

/**
 * PHP Route Parser - PHP Route Configuration Parser
 * 
 * Parses PHP route configuration files with support for closures,
 * dynamic route registration, and complex routing scenarios.
 * 
 * @package Ludelix\Routing\Parsers
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class PhpRouteParser
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function parseFile(string $filePath, RouterInterface $router): void
    {
        if (!file_exists($filePath)) {
            throw new RouteParsingException("PHP route file not found: {$filePath}");
        }

        // Create isolated scope for route file
        $this->executeRouteFile($filePath, $router);
    }

    public function parseArray(array $routes, RouterInterface $router): void
    {
        foreach ($routes as $routeData) {
            $this->parseRoute($routeData, $router);
        }
    }

    protected function executeRouteFile(string $filePath, RouterInterface $router): void
    {
        // Create a closure to isolate the route file scope
        $executeFile = function() use ($filePath, $router) {
            // Make router available in route file
            $route = $router;
            
            return require $filePath;
        };

        $result = $executeFile();

        // If the file returns an array, parse it
        if (is_array($result)) {
            $this->parseArray($result, $router);
        }
    }

    protected function parseRoute(array $routeData, RouterInterface $router): void
    {
        $methods = $routeData['methods'] ?? ['GET'];
        $path = $routeData['path'] ?? null;
        $handler = $routeData['handler'] ?? null;
        $type = $routeData['type'] ?? 'http';

        if (!$path || !$handler) {
            throw new RouteParsingException('Route must have path and handler');
        }

        // Create route based on type
        $route = match($type) {
            'websocket' => $router->websocket($path, $handler),
            'graphql' => $router->graphql($path, $handler),
            'sse' => $router->sse($path, $handler),
            default => $router->match($methods, $path, $handler)
        };

        // Apply configuration
        if (isset($routeData['name'])) {
            $route->name($routeData['name']);
        }

        if (isset($routeData['middleware'])) {
            $route->middleware($routeData['middleware']);
        }

        if (isset($routeData['where'])) {
            $route->where($routeData['where']);
        }

        if (isset($routeData['domain'])) {
            $route->domain($routeData['domain']);
        }
    }
}