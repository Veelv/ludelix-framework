<?php

/**
 * Bootstrap the Ludelix Framework Application
 * 
 * This file bootstraps the framework and returns the application instance.
 * 
 * @package Ludelix\Framework
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Create container instance
$container = new \Ludelix\Core\Container();

// Register service providers
$providers = [
    \Ludelix\Bootstrap\Providers\ConfigProvider::class,
    \Ludelix\Bootstrap\Providers\CacheProvider::class,
    \Ludelix\Bootstrap\Providers\ConsoleProvider::class,
    \Ludelix\Bootstrap\Providers\DatabaseProvider::class,
    \Ludelix\Bootstrap\Providers\BridgeProvider::class,
    \Ludelix\Bootstrap\Providers\ConnectProvider::class,
    \Ludelix\Bootstrap\Providers\TenantProvider::class,
    \Ludelix\Bootstrap\Providers\AssetProvider::class,
    \Ludelix\Bootstrap\Providers\RoutingProvider::class,
    \Ludelix\Bootstrap\Providers\SecurityProvider::class,
    \Ludelix\Bootstrap\Providers\TranslationProvider::class,
    \Ludelix\Bootstrap\Providers\WebSocketProvider::class,
    \Ludelix\Bootstrap\Providers\ObservabilityProvider::class,
    \Ludelix\Bootstrap\Providers\QueueProvider::class,
    \Ludelix\Bootstrap\Providers\GraphQLProvider::class,
    \Ludelix\Bootstrap\Providers\LudouProvider::class,
    \Ludelix\Bootstrap\Providers\PluginProvider::class,
];

// Register providers
foreach ($providers as $provider) {
    if (class_exists($provider)) {
        $providerInstance = new $provider();
        $providerInstance->register($container);
    }
}

// Boot providers
foreach ($providers as $provider) {
    if (class_exists($provider)) {
        $providerInstance = new $provider();
        if (method_exists($providerInstance, 'boot')) {
            $providerInstance->boot($container);
        }
    }
}

return $container; 