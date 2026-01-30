<?php

namespace Ludelix\Core;

use Ludelix\Interface\Config\ConfigInterface;
use Ludelix\Config\Loader\PhpConfigLoader;
use Ludelix\Config\Loader\EnvConfigLoader;
use Ludelix\Config\Loader\ChainLoader;

class Config implements ConfigInterface
{
    protected array $items = [];
    protected ChainLoader $loader;

    public function __construct(string $configPath = null)
    {
        $this->loader = new ChainLoader([
            new PhpConfigLoader(),
            new EnvConfigLoader(),
        ]);

        if ($configPath) {
            $this->loadFromPath($configPath);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getFromArray($this->items, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setInArray($this->items, $key, $value);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function forget(string $key): void
    {
        $this->unsetInArray($this->items, $key);
    }

    public function load(string $path): void
    {
        $config = $this->loader->load($path);
        $this->items = array_merge($this->items, $config);
    }

    protected function loadFromPath(string $configPath): void
    {
        if (!is_dir($configPath)) {
            return;
        }

        $files = glob($configPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    protected function getFromArray(array $array, string $key, mixed $default = null): mixed
    {
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    protected function setInArray(array &$array, string $key, mixed $value): void
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    protected function unsetInArray(array &$array, string $key): void
    {
        if (strpos($key, '.') === false) {
            unset($array[$key]);
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($current[$keys[$i]]) || !is_array($current[$keys[$i]])) {
                return;
            }
            $current = &$current[$keys[$i]];
        }

        unset($current[end($keys)]);
    }
}