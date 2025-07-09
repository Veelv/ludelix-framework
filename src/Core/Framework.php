<?php

namespace Ludelix\Core;

use Ludelix\Interface\FrameworkInterface;
use Ludelix\Interface\DI\ContainerInterface;
use Ludelix\Bootstrap\Runtime\EnvironmentLoader;
use Ludelix\Bootstrap\Runtime\ServiceRegistrar;

class Framework implements FrameworkInterface
{
    protected static ?Framework $instance = null;
    protected ContainerInterface $container;
    protected string $basePath;
    protected bool $booted = false;
    protected array $serviceProviders = [];

    public static function getInstance(): ?Framework
    {
        return static::$instance;
    }

    public static function setInstance(Framework $instance): void
    {
        static::$instance = $instance;
    }

    public function __construct(string $basePath = null)
    {
        $this->basePath = $basePath ?: getcwd();
        $this->container = new Container();
        $this->registerBaseBindings();
        static::setInstance($this);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadEnvironment();
        $this->registerServices();
        $this->bootServices();
        
        $this->booted = true;
    }

    public function run(): void
    {
        $this->boot();
        
        // Handle HTTP request or Console command
        if ($this->isConsole()) {
            $this->runConsole();
        } else {
            $this->runHttp();
        }
    }

    public function terminate(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'terminate')) {
                $provider->terminate();
            }
        }
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    public function version(): string
    {
        return '1.0.3';
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function configPath(): string
    {
        return $this->basePath . '/config';
    }

    public function storagePath(): string
    {
        return $this->basePath . '/cubby';
    }

    public function environment(): string
    {
        return $_ENV['APP_ENV'] ?? 'production';
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function isDebug(): bool
    {
        return (bool) ($_ENV['APP_DEBUG'] ?? false);
    }

    protected function registerBaseBindings(): void
    {
        $this->container->instance(FrameworkInterface::class, $this);
        $this->container->instance(ContainerInterface::class, $this->container);
        $this->container->instance('app', $this);
        $this->container->instance('container', $this->container);
    }

    protected function loadEnvironment(): void
    {
        $loader = new EnvironmentLoader($this->basePath);
        $loader->load();
    }

    protected function registerServices(): void
    {
        $registrar = new ServiceRegistrar($this->container);
        $this->serviceProviders = $registrar->register();
    }

    protected function bootServices(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    protected function isConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }

    protected function runConsole(): void
    {
        // Console handling will be implemented later
        echo "Console mode not implemented yet\n";
    }

    protected function runHttp(): void
    {
        // HTTP handling will be implemented later
        echo "HTTP mode not implemented yet\n";
    }
}