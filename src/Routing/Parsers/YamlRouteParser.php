<?php

namespace Ludelix\Routing\Parsers;

use Ludelix\Interface\Routing\RouterInterface;
use Ludelix\Routing\Exceptions\RouteParsingException;

/**
 * YAML Route Parser - Advanced YAML Route Configuration Parser
 * 
 * Parses YAML route configuration files with support for complex routing
 * scenarios including multi-protocol routes, middleware, and constraints.
 * 
 * @package Ludelix\Routing\Parsers
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class YamlRouteParser
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function parse(string $yamlContent, RouterInterface $router): void
    {
        $data = $this->parseYaml($yamlContent);

        if (!isset($data['routes']) || !is_array($data['routes'])) {
            throw new RouteParsingException('Invalid YAML structure: missing routes array');
        }

        foreach ($data['routes'] as $routeData) {
            $this->parseRoute($routeData, $router);
        }
    }

    public function parseFile(string $filePath, RouterInterface $router): void
    {
        if (!file_exists($filePath)) {
            throw new RouteParsingException("YAML file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $this->parse($content, $router);
    }

    protected function parseRoute(array $routeData, RouterInterface $router): void
    {
        $path = $routeData['path'] ?? null;
        $handler = $routeData['handler'] ?? null;
        $methods = $this->parseMethods($routeData['method'] ?? 'GET');
        $type = $routeData['type'] ?? 'http';

        if (!$path || !$handler) {
            throw new RouteParsingException('Route must have path and handler');
        }


        // Create route based on type
        $route = match ($type) {
            'websocket' => $router->websocket($path, $handler),
            'graphql' => $router->graphql($path, $handler),
            'sse' => $router->sse($path, $handler),
            default => $router->match($methods, $path, $handler)
        };

        // Apply route configuration
        if (isset($routeData['name'])) {
            $route->name($routeData['name']);
        }

        if (isset($routeData['middleware'])) {
            $route->middleware((array) $routeData['middleware']);
        }

        if (isset($routeData['where'])) {
            $route->where($routeData['where']);
        }

        if (isset($routeData['domain'])) {
            $route->domain($routeData['domain']);
        }

        if (isset($routeData['connect'])) {
            $route->connect($routeData['connect']);
        }

        if (isset($routeData['graphql'])) {
            $route->graphql($routeData['graphql']);
        }

        if (isset($routeData['version'])) {
            $route->version($routeData['version']);
        }

        if (isset($routeData['throttle'])) {
            $throttle = $routeData['throttle'];
            if (is_string($throttle) && str_contains($throttle, ',')) {
                [$requests, $minutes] = explode(',', $throttle, 2);
                $route->throttle((int) $requests, (int) $minutes);
            }
        }
    }

    protected function parseMethods(mixed $methods): array
    {
        if (is_string($methods)) {
            return [strtoupper($methods)];
        }

        if (is_array($methods)) {
            return array_map('strtoupper', $methods);
        }

        return ['GET'];
    }

    protected function parseYaml(string $content): array
    {
        // Simple YAML parser implementation
        $lines = explode("\n", $content);
        $result = [];
        $currentSection = null;
        $currentRoute = null;
        $indent = 0;

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (empty($line) || str_starts_with(trim($line), '#')) {
                continue;
            }

            $currentIndent = strlen($line) - strlen(ltrim($line));
            $trimmed = trim($line);

            if ($currentIndent === 0) {
                if (str_ends_with($trimmed, ':')) {
                    $currentSection = rtrim($trimmed, ':');
                    $result[$currentSection] = [];
                }
            } elseif ($currentSection === 'routes' && $currentIndent === 2) {
                if (str_starts_with($trimmed, '- ')) {
                    $currentRoute = [];
                    $result['routes'][] = &$currentRoute;
                    $this->parseKeyValue(substr($trimmed, 2), $currentRoute);
                }
            } elseif ($currentRoute !== null && $currentIndent === 4) {
                $this->parseKeyValue($trimmed, $currentRoute);
            } elseif ($currentRoute !== null && $currentIndent === 6) {
                $this->parseNestedValue($trimmed, $currentRoute);
            }
        }

        return $result;
    }

    protected function parseKeyValue(string $line, array &$target): void
    {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (empty($value)) {
                $target[$key] = [];
                return;
            }

            // Handle arrays
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $value = trim($value, '[]');
                $target[$key] = array_map('trim', explode(',', $value));
                return;
            }

            $target[$key] = $value;
        }
    }

    protected function parseNestedValue(string $line, array &$target): void
    {
        if (str_contains($line, ':')) {
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Find the parent key
            $parentKey = array_key_last($target);
            if ($parentKey && is_array($target[$parentKey])) {
                $target[$parentKey][$key] = $value;
            }
        }
    }
}