<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;

class CacheProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->container->singleton('cache', function ($container) {
            // Cache implementation will be added later
            return new \stdClass();
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}