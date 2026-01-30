<?php

namespace Ludelix\Routing\Generators;

use Ludelix\Routing\Core\RouteCollection;
use Ludelix\Interface\Routing\RouteInterface;

/**
 * URL Generator - Advanced URL Generation System
 * 
 * High-performance URL generation with support for named routes,
 * parameter binding, and multi-domain configurations.
 * 
 * @package Ludelix\Routing\Generators
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class UrlGenerator
{
    protected RouteCollection $routes;
    protected array $config;
    protected string $baseUrl;
    protected bool $forceHttps;

    public function __construct(RouteCollection $routes, array $config = [])
    {
        $this->routes = $routes;
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? $this->detectBaseUrl();
        $this->forceHttps = $config['force_https'] ?? false;
    }

    public function route(string $name, array $parameters = [], array $options = []): string
    {
        $route = $this->routes->getByName($name);
        
        if (!$route) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        return $this->generateUrl($route, $parameters, $options);
    }

    public function to(string $path, array $parameters = [], array $options = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $this->applyOptions($url, $options);
    }

    public function secure(string $path, array $parameters = []): string
    {
        return $this->to($path, $parameters, ['secure' => true]);
    }

    public function asset(string $path): string
    {
        $assetUrl = $this->config['asset_url'] ?? $this->baseUrl;
        return rtrim($assetUrl, '/') . '/' . ltrim($path, '/');
    }

    protected function generateUrl(RouteInterface $route, array $parameters, array $options): string
    {
        $path = $route->getPath();
        $routeParameters = $route->getParameterNames();
        
        // Replace route parameters
        foreach ($routeParameters as $parameter) {
            if (isset($parameters[$parameter])) {
                $path = str_replace('{' . $parameter . '}', $parameters[$parameter], $path);
                unset($parameters[$parameter]);
            }
        }

        // Check for missing required parameters
        if (preg_match('/\{[^}]+\}/', $path)) {
            throw new \InvalidArgumentException("Missing required parameters for route '{$route->getName()}'");
        }

        // Build full URL
        $url = $this->baseUrl . $path;

        // Add query parameters
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $this->applyOptions($url, $options);
    }

    protected function applyOptions(string $url, array $options): string
    {
        // Force HTTPS if requested or configured
        if (($options['secure'] ?? false) || $this->forceHttps) {
            $url = preg_replace('/^http:/', 'https:', $url);
        }

        // Add fragment if specified
        if (isset($options['fragment'])) {
            $url .= '#' . $options['fragment'];
        }

        return $url;
    }

    protected function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $port = $_SERVER['SERVER_PORT'] ?? 80;
        
        // Don't include standard ports
        if (($protocol === 'http' && $port == 80) || ($protocol === 'https' && $port == 443)) {
            return "{$protocol}://{$host}";
        }
        
        return "{$protocol}://{$host}:{$port}";
    }

    public function setBaseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}