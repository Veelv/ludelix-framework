<?php

namespace Ludelix\Routing\Core;

use Ludelix\Interface\Routing\RouteInterface;

/**
 * Route Collection - High-Performance Route Storage and Management
 * 
 * Manages collections of routes with optimized storage, indexing, and retrieval
 * for maximum performance in enterprise applications.
 * 
 * @package Ludelix\Routing\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class RouteCollection
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected array $methodIndex = [];
    protected array $domainIndex = [];
    protected bool $compiled = false;

    public function add(RouteInterface $route): void
    {
        $this->routes[] = $route;
        
        // Index by name
        if ($name = $route->getName()) {
            $this->namedRoutes[$name] = $route;
        }
        
        // Index by methods
        foreach ($route->getMethods() as $method) {
            $this->methodIndex[$method][] = $route;
        }
        
        // Index by domain if specified
        if ($domain = $route->getOptions()['domain'] ?? null) {
            $this->domainIndex[$domain][] = $route;
        }
        
        $this->compiled = false;
    }

    public function getByName(string $name): ?RouteInterface
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getByMethod(string $method): array
    {
        return $this->methodIndex[strtoupper($method)] ?? [];
    }

    public function getByDomain(string $domain): array
    {
        return $this->domainIndex[$domain] ?? [];
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function hasRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->namedRoutes = [];
        $this->methodIndex = [];
        $this->domainIndex = [];
        $this->compiled = false;
    }

    public function compile(): void
    {
        if ($this->compiled) {
            return;
        }

        // Sort routes by specificity (more specific routes first)
        usort($this->routes, function(RouteInterface $a, RouteInterface $b) {
            $aPath = $a->getPath();
            $bPath = $b->getPath();
            
            // Routes with fewer parameters are more specific
            $aParams = substr_count($aPath, '{');
            $bParams = substr_count($bPath, '{');
            
            if ($aParams !== $bParams) {
                return $aParams <=> $bParams;
            }
            
            // Longer paths are more specific
            return strlen($bPath) <=> strlen($aPath);
        });

        $this->compiled = true;
    }

    public function isCompiled(): bool
    {
        return $this->compiled;
    }

    public function toArray(): array
    {
        return array_map(fn(RouteInterface $route) => $route->toArray(), $this->routes);
    }
}