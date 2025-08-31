<?php

namespace Ludelix\Routing\Parsers;

use Ludelix\Interface\Routing\RouterInterface;
use Ludelix\Routing\Exceptions\RouteParsingException;

/**
 * JSON Route Parser - JSON Route Configuration Parser
 * 
 * Parses JSON route configuration with support for API-driven
 * route management and dynamic route registration.
 * 
 * @package Ludelix\Routing\Parsers
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class JsonRouteParser
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function parse(string $jsonContent, RouterInterface $router): void
    {
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RouteParsingException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($data['routes']) || !is_array($data['routes'])) {
            throw new RouteParsingException('Invalid JSON structure: missing routes array');
        }

        foreach ($data['routes'] as $routeData) {
            $this->parseRoute($routeData, $router);
        }
    }

    public function parseFile(string $filePath, RouterInterface $router): void
    {
        if (!file_exists($filePath)) {
            throw new RouteParsingException("JSON file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $this->parse($content, $router);
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

        // Create route
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
    }
}