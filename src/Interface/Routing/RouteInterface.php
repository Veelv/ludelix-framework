<?php

namespace Ludelix\Interface\Routing;

/**
 * Route Interface - Individual Route Configuration Contract
 * 
 * Defines the contract for individual route instances within the Ludelix routing system.
 * Each route represents a single endpoint configuration with comprehensive metadata,
 * middleware pipeline, and execution context.
 * 
 * @package Ludelix\Interface\Routing
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
interface RouteInterface
{
    /**
     * Get route HTTP methods
     * 
     * @return array Array of HTTP methods this route responds to
     */
    public function getMethods(): array;

    /**
     * Get route path pattern
     * 
     * @return string Route path with parameter placeholders
     */
    public function getPath(): string;

    /**
     * Get route handler
     * 
     * @return mixed Route handler (controller, closure, callable)
     */
    public function getHandler(): mixed;

    /**
     * Get route name
     * 
     * @return string|null Route name for URL generation
     */
    public function getName(): ?string;

    /**
     * Set route name
     * 
     * @param string $name Route name
     * @return self Fluent interface
     */
    public function name(string $name): self;

    /**
     * Add middleware to route
     * 
     * @param array|string $middleware Middleware classes or names
     * @return self Fluent interface
     */
    public function middleware(array|string $middleware): self;

    /**
     * Set route prefix
     * 
     * @param string $prefix URL prefix
     * @return self Fluent interface
     */
    public function prefix(string $prefix): self;

    /**
     * Set route domain constraint
     * 
     * @param string $domain Domain pattern
     * @return self Fluent interface
     */
    public function domain(string $domain): self;

    /**
     * Add route parameter constraints
     * 
     * @param array $constraints Parameter validation patterns
     * @return self Fluent interface
     */
    public function where(array $constraints): self;

    /**
     * Set Connect component for SPA integration
     * 
     * @param string $component Component name
     * @return self Fluent interface
     */
    public function connect(string $component): self;

    /**
     * Set GraphQL configuration
     * 
     * @param array $config GraphQL query/mutation configuration
     * @return self Fluent interface
     */
    public function graphql(array $config): self;

    /**
     * Set API version
     * 
     * @param string $version API version identifier
     * @return self Fluent interface
     */
    public function version(string $version): self;

    /**
     * Set rate limiting configuration
     * 
     * @param int $requests Maximum requests
     * @param int $minutes Time window in minutes
     * @return self Fluent interface
     */
    public function throttle(int $requests, int $minutes): self;

    /**
     * Check if route matches request
     * 
     * @param string $method HTTP method
     * @param string $path Request path
     * @return bool True if route matches
     */
    public function matches(string $method, string $path): bool;

    /**
     * Get route options
     * 
     * @return array Route options
     */
    public function getOptions(): array;

    /**
     * Get route configuration array
     * 
     * @return array Complete route configuration
     */
    public function toArray(): array;
}