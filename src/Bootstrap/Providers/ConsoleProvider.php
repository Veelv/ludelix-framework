<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;

class ConsoleProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->container->singleton('Ludelix\Core\Console\ConsoleKernel', function ($container) {
            // Console kernel implementation will be added later
            return new \stdClass();
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}