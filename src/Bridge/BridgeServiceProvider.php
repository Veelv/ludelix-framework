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
        
        // Register Response service
        $this->container->singleton('response', function ($container) {
            return new \Ludelix\PRT\Response();
        });
        
        // Register Asset service
        $this->container->singleton('asset', function ($container) {
            $config = $container->has('config') ? $container->get('config')->get('assets', []) : [];
            return new \Ludelix\Asset\Core\AssetManager($config);
        });
    }

    public function boot(): void
    {
        // Initialize Bridge instance
        Bridge::instance($this->container);
    }
}