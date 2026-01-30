<?php

namespace Ludelix\Routing\Core;

use Ludelix\Interface\Routing\RouteInterface;

/**
 * Route - Individual Route Configuration and Management
 * 
 * Represents a single route within the Ludelix routing system with comprehensive
 * configuration, middleware pipeline, and execution context management.
 * 
 * @package Ludelix\Routing\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class Route implements RouteInterface
{
    protected array $methods;
    protected string $path;
    protected mixed $handler;
    protected ?string $name = null;
    protected array $middleware = [];
    protected array $constraints = [];
    protected array $defaults = [];
    protected array $options = [];
    protected string $compiledRegex = '';
    protected array $parameterNames = [];

    public function __construct(array $methods, string $path, mixed $handler, array $options = [])
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->path = $path;
        $this->handler = $handler;
        $this->options = $options;
        
        $this->name = $options['name'] ?? null;
        $middleware = $options['middleware'] ?? [];
        $this->middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->constraints = $options['where'] ?? [];
        $this->defaults = $options['defaults'] ?? [];
        
        $this->compileRoute();
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHandler(): mixed
    {
        return $this->handler;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->path = '/' . trim($prefix, '/') . '/' . ltrim($this->path, '/');
        $this->compileRoute();
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->options['domain'] = $domain;
        return $this;
    }

    public function where(array $constraints): self
    {
        $this->constraints = array_merge($this->constraints, $constraints);
        $this->compileRoute();
        return $this;
    }

    public function connect(string $component): self
    {
        $this->options['connect'] = $component;
        return $this;
    }

    public function graphql(array $config): self
    {
        $this->options['graphql'] = $config;
        return $this;
    }

    public function version(string $version): self
    {
        $this->options['version'] = $version;
        return $this;
    }

    public function throttle(int $requests, int $minutes): self
    {
        $this->middleware[] = "throttle:{$requests},{$minutes}";
        return $this;
    }

    public function matches(string $method, string $path): bool
    {
        if (!in_array($method, $this->methods)) {
            return false;
        }

        return preg_match($this->compiledRegex, $path);
    }

    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'path' => $this->path,
            'handler' => $this->handler,
            'name' => $this->name,
            'middleware' => $this->middleware,
            'constraints' => $this->constraints,
            'options' => $this->options,
        ];
    }

    public function getCompiledRegex(): string
    {
        return $this->compiledRegex;
    }

    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function compileRoute(): void
    {
        $path = $this->path;
        $this->parameterNames = [];
        
        // Extract parameter names
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        $this->parameterNames = $matches[1];
        
        // Replace parameters with regex patterns
        $regex = preg_replace_callback('/\{([^}]+)\}/', function($matches) {
            $param = $matches[1];
            $pattern = $this->constraints[$param] ?? '[^/]+';
            return "({$pattern})";
        }, $path);

        if ($regex !== '/') {
            $regex = $regex . '/?';
        }
        
        $this->compiledRegex = '#^' . $regex . '$#';
    }
}