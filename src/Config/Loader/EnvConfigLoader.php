<?php

namespace Ludelix\Config\Loader;

class EnvConfigLoader
{
    public function canLoad(string $path): bool
    {
        return pathinfo($path, PATHINFO_EXTENSION) === 'env' || basename($path) === '.env';
    }

    public function load(string $path): array
    {
        if (!$this->canLoad($path) || !file_exists($path)) {
            return [];
        }

        $config = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $config[trim($key)] = $this->parseValue(trim($value));
            }
        }

        return $config;
    }

    protected function parseValue(string $value): mixed
    {
        // Remove quotes
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            return $matches[1];
        }
        
        if (preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        // Convert boolean and null
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $value,
        };
    }
}