<?php

namespace Ludelix\Routing\Helpers;

class RouteLoader
{
    public static function loadAll($router, $routesDir)
    {
        if (!is_dir($routesDir)) return;
        foreach (scandir($routesDir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $routesDir . '/' . $file;
            if (is_file($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                if ($ext === 'php') {
                    $router->loadFromPhp($filePath);
                } elseif ($ext === 'yaml' || $ext === 'yml') {
                    $router->loadFromYaml($filePath);
                } elseif ($ext === 'json') {
                    $router->loadFromJson(file_get_contents($filePath));
                }
            }
        }
    }
} 