<?php

namespace Ludelix\Cache;

use Ludelix\Bootstrap\Providers\ServiceProvider;

/**
 * Cache Service Provider
 * 
 * Registers cache services and drivers
 */
class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('cache', function ($container) {
            $config = $container->get('config')->get('cache', []);
            return new CacheManager($config);
        });

        $this->container->alias('cache', CacheManager::class);
    }

    public function boot(): void
    {
        // Register cache cleanup command
        if ($this->container->has('console')) {
            $console = $this->container->get('console');
            $console->add(new Commands\CacheClearCommand());
            $console->add(new Commands\CacheCleanupCommand());
        }
    }
}