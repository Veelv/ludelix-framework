<?php

namespace Ludelix\Bridge;

use Ludelix\Bootstrap\Providers\ServiceProvider;

/**
 * Bridge Service Provider
 */
class BridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('bridge', function ($container) {
            return new Bridge($container);
        });

        $this->container->alias('bridge', Bridge::class);
    }

    public function boot(): void
    {
        // Initialize Bridge instance
        Bridge::instance($this->container);
    }
}