<?php

namespace Ludelix\Flash\Providers;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Flash\Core\FlashManager;

/**
 * FlashServiceProvider - Registers the flash service in the container
 * 
 * This service provider registers the FlashManager class in the service container.
 * 
 * @package Ludelix\Flash\Providers
 */
class FlashServiceProvider extends ServiceProvider
{
    /**
     * Register the flash service
     *
     * @return void
     */
    public function register(): void
    {
        $this->container->singleton('flash', function () {
            return new FlashManager();
        });

        $this->container->alias('flash', FlashManager::class);
    }
}