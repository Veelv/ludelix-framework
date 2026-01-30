<?php

namespace Ludelix\Bootstrap\Providers;

use Ludelix\Interface\DI\ContainerInterface;

class ProviderDiscovery
{
    protected ContainerInterface $container;
    protected string $basePath;
    protected array $discoveredProviders = [];

    public function __construct(ContainerInterface $container, string $basePath)
    {
        $this->container = $container;
        $this->basePath = $basePath;
    }

    public function discover(): array
    {
        $providers = [];

        // Core framework providers (priority order)
        $coreProviders = [
            'Ludelix\Bootstrap\Providers\ConfigProvider',
            'Ludelix\Bootstrap\Providers\CacheProvider',
            'Ludelix\Bridge\BridgeServiceProvider',
            'Ludelix\Connect\ConnectServiceProvider',
            'Ludelix\Bootstrap\Providers\DatabaseProvider',
            'Ludelix\Bootstrap\Providers\LudouProvider',
            'Ludelix\Fluid\FluidServiceProvider',
            'Ludelix\ApiExplorer\ApiExplorerServiceProvider',
        ];

        foreach ($coreProviders as $provider) {
            if (class_exists($provider)) {
                $providers[] = new $provider($this->container);
            }
        }

        // Auto-discover app providers
        $appProviders = $this->discoverAppProviders();
        $providers = array_merge($providers, $appProviders);

        return $providers;
    }

    protected function discoverAppProviders(): array
    {
        $providers = [];
        $providersPath = $this->basePath . '/app/Providers';

        if (!is_dir($providersPath)) {
            return $providers;
        }

        $files = glob($providersPath . '/*Provider.php');

        foreach ($files as $file) {
            $className = $this->getClassNameFromFile($file);
            if ($className && class_exists($className)) {
                $providers[] = new $className($this->container);
            }
        }

        return $providers;
    }

    protected function getClassNameFromFile(string $file): ?string
    {
        $basename = basename($file, '.php');

        // Try common namespaces
        $namespaces = [
            'App\\Providers\\',
            'App\\',
        ];

        foreach ($namespaces as $namespace) {
            $className = $namespace . $basename;
            if (class_exists($className)) {
                return $className;
            }
        }

        return null;
    }
}