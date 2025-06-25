<?php

if (!function_exists('app')) {
    function app(string $abstract = null): mixed
    {
        $instance = \Ludelix\Core\Framework::getInstance();
        
        if (!$instance) {
            throw new \RuntimeException('Framework not initialized');
        }
        
        if (is_null($abstract)) {
            return $instance;
        }

        return $instance->container()->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(string $key = null, mixed $default = null): mixed
    {
        try {
            if (is_null($key)) {
                return app('config');
            }

            return app('config')->get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app()->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return app()->configPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('cubby_path')) {
    function cubby_path(string $path = ''): string
    {
        return app()->storagePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}