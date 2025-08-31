<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Core\Config;
use Ludelix\Config\Repository\CachedRepository;

class ConfigProvider
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        $this->container->singleton('config', function ($container) {
            $app = $container->make('app');
            $config = new Config($app->configPath());
            
            return new CachedRepository($config);
        });
    }

    public function boot(): void
    {
        // Boot logic if needed
    }
}