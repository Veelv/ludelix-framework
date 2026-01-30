<?php

namespace Ludelix\Core\Providers;

use Psr\Container\ContainerInterface;
use Ludelix\Core\Container;

abstract class AbstractServiceProvider
{
    abstract public function register(Container $container): void;
    
    public function boot(ContainerInterface $container): void
    {
        // Override in child classes if needed
    }
}
