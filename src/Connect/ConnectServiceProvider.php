<?php

namespace Ludelix\Connect;

use Ludelix\Bootstrap\Providers\ServiceProvider;
use Ludelix\Connect\Core\ConnectManager;
use Ludelix\Connect\Core\ComponentResolver;
use Ludelix\Connect\Core\ResponseBuilder;

/**
 * Connect Service Provider
 * 
 * Registers LudelixConnect services and configures the SPA integration system.
 * 
 * @package Ludelix\Connect
 * @author Ludelix Framework Team
 */
class ConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('connect.manager', function ($container) {
            return new ConnectManager(
                $container->get('events'),
                $container->get('cache'),
                $container->get('logger'),
                $container->get('config')->get('connect', [])
            );
        });

        $this->container->singleton('connect.resolver', function ($container) {
            return new ComponentResolver(
                $container->get('connect.manager'),
                $container->get('cache'),
                $container->get('logger'),
                $container->get('config')->get('connect.resolver', [])
            );
        });

        $this->container->singleton('connect.response', function ($container) {
            return new ResponseBuilder(
                $container->get('ludou'),
                $container->get('config')->get('connect.response', [])
            );
        });

        $this->container->singleton('connect', function ($container) {
            return new Connect(
                $container->get('connect.manager'),
                $container->get('connect.resolver'),
                $container->get('connect.response'),
                $container->get('connect.ssr'),
                $container->get('connect.sync'),
                $container->get('events'),
                $container->get('logger'),
                $container->get('request'),
                $container->get('config')->get('connect', [])
            );
        });

        $this->container->alias('connect', Connect::class);
    }

    public function boot(): void
    {
        // Register default component paths
        $manager = $this->container->get('connect.manager');
        
        $defaultPaths = [
            'frontend/js/pages' => ['priority' => 10],
            'frontend/js/components' => ['priority' => 5],
            'resources/js/pages' => ['priority' => 8],
            'resources/js/components' => ['priority' => 3],
        ];

        foreach ($defaultPaths as $path => $options) {
            $manager->addResolverPath($path, $options);
        }
    }
}