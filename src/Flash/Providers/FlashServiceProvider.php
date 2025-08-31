<?php

namespace Ludelix\Flash\Providers;

use Ludelix\Core\Providers\ServiceProviderInterface;
use Ludelix\Flash\Core\FlashManager;
use Ludelix\Core\Container;

/**
 * FlashServiceProvider - Registers the flash service in the container
 * 
 * This service provider registers the FlashManager class in the service container.
 * 
 * @package Ludelix\Flash\Providers
 */
class FlashServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the flash service
     *
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void
    {
        $container->set('flash', function () {
            return new FlashManager();
        });
        
        $container->set(FlashManager::class, function () {
            return new FlashManager();
        });
    }

    /**
     * Bootstrap any application services
     *
     * @param Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
        // Bootstrap logic if needed
        // Ensure session is started for flash messages to work
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}