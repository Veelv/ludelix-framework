<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Core\Support\Str;

/**
 * Core Service Provider
 * 
 * Registers core framework services
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('str', function () {
            return new Str();
        });

        $this->container->singleton('csrf', function () {
            return new \Ludelix\Security\CsrfManager();
        });

        $this->container->alias('str', Str::class);
        $this->container->alias('csrf', \Ludelix\Security\CsrfManager::class);
    }
}