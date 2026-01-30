<?php

namespace Ludelix\Routing\Middleware;

use Ludelix\Interface\Routing\MiddlewareInterface;
use Ludelix\Core\Container;
use Ludelix\Interface\Logging\LoggerInterface;

/**
 * Middleware Resolver - Middleware Resolution and Instantiation
 * 
 * Resolves middleware from string identifiers to executable instances.
 * Supports middleware aliases, parameterized middleware, and dependency injection.
 * 
 * @package Ludelix\Routing\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class MiddlewareResolver
{
    protected Container $container;
    protected LoggerInterface $logger;
    protected array $aliases = [];
    protected array $resolved = [];
    protected bool $caching = true;

    public function __construct(
        Container $container,
        LoggerInterface $logger,
        array $aliases = []
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->aliases = $aliases;
    }

    /**
     * Resolve middleware from string or array to executable instance
     * 
     * @param string|array|MiddlewareInterface $middleware Middleware to resolve
     * @return array [instance, parameters]
     */
    public function resolve(string|array|MiddlewareInterface $middleware): array
    {
        // Already an instance
        if ($middleware instanceof MiddlewareInterface) {
            return [$middleware, []];
        }

        // Array format: [MiddlewareClass::class, ['param1', 'param2']]
        if (is_array($middleware)) {
            [$class, $params] = $middleware;
            return [$this->resolveClass($class), $params];
        }

        // String format with parameters: "throttle:60,1"
        if (str_contains($middleware, ':')) {
            [$name, $paramString] = explode(':', $middleware, 2);
            $params = explode(',', $paramString);
            return [$this->resolveClass($name), $params];
        }

        // Simple string: "auth" or "App\Middleware\CustomMiddleware"
        return [$this->resolveClass($middleware), []];
    }

    /**
     * Resolve middleware class from name or alias
     * 
     * @param string $name Middleware name, alias, or class
     * @return MiddlewareInterface Middleware instance
     */
    protected function resolveClass(string $name): MiddlewareInterface
    {
        // Check cache
        if ($this->caching && isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        // Resolve alias to class name
        $class = $this->aliases[$name] ?? $name;

        // Validate class exists
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(
                "Middleware class '{$class}' not found. " .
                "Available aliases: " . implode(', ', array_keys($this->aliases))
            );
        }

        // Instantiate via container (supports dependency injection)
        $instance = $this->container->make($class);

        // Validate implements interface
        if (!$instance instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException(
                "Middleware '{$class}' must implement MiddlewareInterface"
            );
        }

        // Cache instance
        if ($this->caching) {
            $this->resolved[$name] = $instance;
        }

        $this->logger->debug("Resolved middleware: {$name} -> {$class}");

        return $instance;
    }

    /**
     * Resolve multiple middleware at once
     * 
     * @param array $middlewareList List of middleware to resolve
     * @return array Array of [instance, parameters] pairs
     */
    public function resolveMany(array $middlewareList): array
    {
        return array_map(
            fn($middleware) => $this->resolve($middleware),
            $middlewareList
        );
    }

    /**
     * Register middleware alias
     * 
     * @param string $alias Alias name
     * @param string $class Middleware class name
     * @return self
     */
    public function alias(string $alias, string $class): self
    {
        $this->aliases[$alias] = $class;
        return $this;
    }

    /**
     * Register multiple aliases
     * 
     * @param array $aliases Alias => Class mapping
     * @return self
     */
    public function aliases(array $aliases): self
    {
        $this->aliases = array_merge($this->aliases, $aliases);
        return $this;
    }

    /**
     * Get all registered aliases
     * 
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Enable or disable instance caching
     * 
     * @param bool $enabled
     * @return self
     */
    public function setCaching(bool $enabled): self
    {
        $this->caching = $enabled;
        return $this;
    }

    /**
     * Clear resolved middleware cache
     * 
     * @return self
     */
    public function clearCache(): self
    {
        $this->resolved = [];
        return $this;
    }
}
