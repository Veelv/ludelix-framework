<?php

namespace Ludelix\Config\Loader;

class PhpConfigLoader
{
    public function canLoad(string $path): bool
    {
        return pathinfo($path, PATHINFO_EXTENSION) === 'php';
    }

    public function load(string $path): array
    {
        if (!$this->canLoad($path) || !file_exists($path)) {
            return [];
        }

        $config = require $path;
        
        return is_array($config) ? $config : [];
    }
}