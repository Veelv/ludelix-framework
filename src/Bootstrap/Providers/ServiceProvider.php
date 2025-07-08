<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;

/**
 * Base class for all service providers in Ludeliz Framework.
 * Provides a minimal contract compatible with ServiceRegistrar.
 */
abstract class ServiceProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register bindings/services into container.
     */
    public function register(): void
    {
        // To be overridden by concrete providers
    }

    /**
     * Boot logic after all providers are registered.
     */
    public function boot(): void
    {
        // Optional override
    }

    /**
     * Graceful termination hook.
     */
    public function terminate(): void
    {
        // Optional override
    }
}
