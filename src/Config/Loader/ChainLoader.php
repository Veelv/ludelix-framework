<?php

namespace Ludelix\Config\Loader;

class ChainLoader
{
    protected array $loaders;

    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders;
    }

    public function addLoader(object $loader): void
    {
        $this->loaders[] = $loader;
    }

    public function load(string $path): array
    {
        foreach ($this->loaders as $loader) {
            if (method_exists($loader, 'canLoad') && $loader->canLoad($path)) {
                return $loader->load($path);
            }
        }

        return [];
    }
}