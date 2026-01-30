<?php

namespace Ludelix\Routing\Binding;

use Ludelix\Core\Container;
use Ludelix\Routing\Exceptions\ModelBindingException;

/**
 * Model Binder - Advanced Route Model Binding System
 * 
 * Provides automatic model resolution and injection for route parameters
 * with support for custom binding strategies, caching, and performance optimization.
 * 
 * @package Ludelix\Routing\Binding
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class ModelBinder
{
    protected Container $container;
    protected array $bindings = [];
    protected array $resolvers = [];
    protected array $cache = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function bind(string $key, string $model, ?callable $resolver = null): void
    {
        $this->bindings[$key] = $model;
        
        if ($resolver) {
            $this->resolvers[$key] = $resolver;
        }
    }

    public function resolve(array $parameters): array
    {
        $resolved = [];
        
        foreach ($parameters as $key => $value) {
            if (isset($this->bindings[$key])) {
                $resolved[$key] = $this->resolveModel($key, $value);
            } else {
                $resolved[$key] = $value;
            }
        }
        
        return $resolved;
    }

    protected function resolveModel(string $key, mixed $value): mixed
    {
        $cacheKey = "{$key}:{$value}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $modelClass = $this->bindings[$key];
        
        if (isset($this->resolvers[$key])) {
            $model = $this->resolvers[$key]($value, $modelClass);
        } else {
            $model = $this->defaultResolver($value, $modelClass);
        }

        if (!$model) {
            throw new ModelBindingException("Model not found for {$key}: {$value}");
        }

        $this->cache[$cacheKey] = $model;
        
        return $model;
    }

    protected function defaultResolver(mixed $value, string $modelClass): mixed
    {
        if ($modelClass === 'User' && is_numeric($value)) {
            return (object) ['id' => $value, 'name' => "User {$value}"];
        }
        
        return null;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}