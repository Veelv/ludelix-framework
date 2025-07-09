<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;

class CacheProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->container->singleton('cache', function ($container) {
            $config = ['default' => 'file', 'drivers' => ['file' => ['path' => 'storage/cache']]];
            if ($container->has('config')) {
                $configService = $container->get('config');
                if (is_object($configService) && method_exists($configService, 'get')) {
                    $config = $configService->get('cache', $config);
                }
            }
            return new \Ludelix\Cache\CacheManager($config);
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}