<?php

if (!function_exists('env')) {
    /**
     * Get environment variable value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $configPath = __DIR__ . '/../../../../config/app.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
            } else {
                $config = [];
            }
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }
}

if (!function_exists('cubby_path')) {
    /**
     * Get cubby storage path
     *
     * @param string $path
     * @return string
     */
    function cubby_path(string $path = ''): string
    {
        // Compute project root from this file path (vendor/ludelix/framework/src/Core/Support)
        $projectRoot = realpath(dirname(__DIR__, 6));
        if (!$projectRoot) {
            // Fallback: normalize without realpath
            $projectRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, dirname(__DIR__, 6));
        }

        $basePath = $projectRoot . DIRECTORY_SEPARATOR . 'cubby';
        $basePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $basePath);

        $fullPath = $path ? $basePath . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $basePath;
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_array($target)) {
                    return $default;
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @param  mixed  $value
     * @param  bool  $overwrite
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (!is_array($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            }
        } elseif (is_array($target)) {
            if ($segments) {
                if (!array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !array_key_exists($segment, $target)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (!function_exists('data_forget')) {
    /**
     * Forget an item from an array or object using dot notation.
     *
     * @param  mixed  $target
     * @param  string|array  $key
     * @return void
     */
    function data_forget(&$target, $key)
    {
        $keys = (array) $key;

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            $parts = explode('.', $key);
            $temp = &$target;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($temp[$part]) && is_array($temp[$part])) {
                    $temp = &$temp[$part];
                } else {
                    continue 2;
                }
            }

            unset($temp[array_shift($parts)]);
        }
    }
}

if (!function_exists('app')) {
    /**
     * Get the available framework instance or resolve a service from the container.
     *
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return mixed|\Ludelix\Core\Framework
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return \Ludelix\Core\Framework::getInstance();
        }

        return \Ludelix\Core\Framework::getInstance()->container()->make($abstract, $parameters);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return \Ludelix\Core\Framework::getInstance()->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return \Ludelix\Core\Framework::getInstance()->storagePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
