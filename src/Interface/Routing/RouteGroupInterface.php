<?php

namespace Ludelix\Interface\Routing;

/**
 * Route Group Interface - Route Collection Management Contract
 * 
 * Defines the contract for route groups that share common attributes
 * such as middleware, prefixes, namespaces, and other configuration.
 * 
 * @package Ludelix\Interface\Routing
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
interface RouteGroupInterface
{
    /**
     * Get group attributes
     * 
     * @return array Group configuration attributes
     */
    public function getAttributes(): array;
    
    /**
     * Get routes in this group
     * 
     * @return array Routes belonging to this group
     */
    public function getRoutes(): array;
    
    /**
     * Add route to group
     * 
     * @param RouteInterface $route Route to add
     * @return self Fluent interface
     */
    public function addRoute(RouteInterface $route): self;
    
    /**
     * Set group middleware
     * 
     * @param array $middleware Middleware stack
     * @return self Fluent interface
     */
    public function middleware(array $middleware): self;
    
    /**
     * Set group prefix
     * 
     * @param string $prefix URL prefix
     * @return self Fluent interface
     */
    public function prefix(string $prefix): self;
    
    /**
     * Set group namespace
     * 
     * @param string $namespace Controller namespace
     * @return self Fluent interface
     */
    public function namespace(string $namespace): self;
}