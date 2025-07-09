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
        $this->container->singleton('events', function ($container) {
            return new \Ludelix\Core\EventDispatcher();
        });

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

        $this->container->singleton('logger', function ($container) {
            // Use PSR-3 logger if available, otherwise fallback to Ludelix\Core\Logger
            if (class_exists('Monolog\\Logger')) {
                return new \Monolog\Logger('ludelix');
            }
            return new class extends \Ludelix\Core\Logger implements \Psr\Log\LoggerInterface {
                // Implement all PSR-3 methods delegating to Ludelix\Core\Logger
                public function emergency($message, array $context = []): void { $this->log('emergency', $message, $context); }
                public function alert($message, array $context = []): void { $this->log('alert', $message, $context); }
                public function critical($message, array $context = []): void { $this->log('critical', $message, $context); }
                public function error($message, array $context = []): void { $this->log('error', $message, $context); }
                public function warning($message, array $context = []): void { $this->log('warning', $message, $context); }
                public function notice($message, array $context = []): void { $this->log('notice', $message, $context); }
                public function info($message, array $context = []): void { $this->log('info', $message, $context); }
                public function debug($message, array $context = []): void { $this->log('debug', $message, $context); }
                public function log($level, $message, array $context = []): void { parent::log($level, $message, $context); }
            };
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