<?php

namespace Ludelix\Routing\Core;

use Ludelix\Interface\Routing\RouteInterface;
use Ludelix\Interface\Routing\RouteGroupInterface;

/**
 * Route Group - Route Collection with Shared Attributes
 * 
 * Manages groups of routes that share common attributes like middleware,
 * prefixes, namespaces, and other configuration options.
 * 
 * @package Ludelix\Routing\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class RouteGroup implements RouteGroupInterface
{
    protected array $attributes = [];
    protected array $routes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function addRoute(RouteInterface $route): self
    {
        $this->routes[] = $route;
        return $this;
    }

    public function middleware(array $middleware): self
    {
        $this->attributes['middleware'] = array_merge(
            $this->attributes['middleware'] ?? [],
            $middleware
        );
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->attributes['prefix'] = $prefix;
        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->attributes['namespace'] = $namespace;
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->attributes['domain'] = $domain;
        return $this;
    }

    public function name(string $name): self
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    public function where(array $constraints): self
    {
        $this->attributes['where'] = array_merge(
            $this->attributes['where'] ?? [],
            $constraints
        );
        return $this;
    }
}