<?php

namespace Ludelix\Bootstrap\Runtime;

use Ludelix\Interface\DI\ContainerInterface;

class ServiceRegistrar
{
    protected ContainerInterface $container;
    protected array $providers = [
        \Ludelix\Bootstrap\Providers\ConfigProvider::class,
        \Ludelix\Bootstrap\Providers\CacheProvider::class,
        \Ludelix\Bootstrap\Providers\RequestServiceProvider::class,
        \Ludelix\Bootstrap\Providers\RouteServiceProvider::class,
        \Ludelix\Bootstrap\Providers\LudouProvider::class,
        \Ludelix\Bootstrap\Providers\CoreServiceProvider::class,
        \Ludelix\Bridge\BridgeServiceProvider::class,
        \Ludelix\Bootstrap\Providers\DatabaseProvider::class,
        \Ludelix\Bootstrap\Providers\ConsoleProvider::class,
        \Ludelix\Auth\AuthServiceProvider::class,
        \Ludelix\Fluid\FluidServiceProvider::class,
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): array
    {
        $instances = [];

        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass($this->container);

            if (method_exists($provider, 'register')) {
                $provider->register();
            }

            $instances[] = $provider;
        }

        return $instances;
    }
}