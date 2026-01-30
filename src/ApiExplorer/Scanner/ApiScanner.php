<?php

namespace Ludelix\ApiExplorer\Scanner;

use Ludelix\Routing\Core\Router;
use Ludelix\ApiExplorer\Attributes\QueryParam;
use Ludelix\ApiExplorer\Attributes\BodyParam;
use Ludelix\Auth\Attributes\Authorize;
use ReflectionClass;
use ReflectionMethod;

/**
 * ApiScanner - Scans routes and controllers to extract API metadata.
 */
class ApiScanner
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Scan all routes and return a structured schema.
     */
    public function scan(): array
    {
        $routes = $this->router->getRoutes();
        $schema = [];

        foreach ($routes as $route) {
            $handler = $route->getHandler();
            if (!is_string($handler) || !str_contains($handler, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $handler);
            if (!class_exists($controller) || !method_exists($controller, $method)) {
                continue;
            }

            $endpoint = [
                'name' => $route->getName() ?? $this->generateName($route->getPath()),
                'method' => $route->getMethods()[0] ?? 'GET',
                'path' => $route->getPath(),
                'auth' => $this->checkAuth($controller, $method),
                'queryParams' => $this->getAttributes($controller, $method, QueryParam::class),
                'bodyParams' => $this->getAttributes($controller, $method, BodyParam::class),
            ];

            $schema[] = $endpoint;
        }

        return $schema;
    }

    /**
     * Check if the endpoint requires authorization.
     */
    protected function checkAuth(string $controller, string $method): bool
    {
        $classRef = new ReflectionClass($controller);
        if ($classRef->getAttributes(Authorize::class))
            return true;

        $methodRef = new ReflectionMethod($controller, $method);
        return !empty($methodRef->getAttributes(Authorize::class));
    }

    /**
     * Extract parameters from attributes.
     */
    protected function getAttributes(string $controller, string $method, string $attributeClass): array
    {
        $reflection = new ReflectionMethod($controller, $method);
        $attributes = $reflection->getAttributes($attributeClass);
        $params = [];

        foreach ($attributes as $attr) {
            $instance = $attr->newInstance();
            $params[] = [
                'name' => $instance->name,
                'type' => $this->mapType($instance->type),
                'required' => $instance->required,
                'description' => $instance->description
            ];
        }

        return $params;
    }

    /**
     * Maps PHP/Ludelix types to TypeScript types.
     */
    protected function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer', 'float', 'double', 'number' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'any[]',
            'object' => 'any',
            default => 'string'
        };
    }

    /**
     * Fallback name generation.
     */
    protected function generateName(string $path): string
    {
        $clean = preg_replace('/[^a-z0-9]/i', '_', trim($path, '/'));
        return strtolower($clean) ?: 'index';
    }
}
