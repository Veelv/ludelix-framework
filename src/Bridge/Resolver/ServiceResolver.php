<?php

namespace Ludelix\Bridge\Resolver;

use Ludelix\Core\Container;
use Ludelix\Bridge\Cache\BridgeCache;

/**
 * Service Resolver
 * 
 * Responsible for resolving and retrieving services from the application container.
 */
class ServiceResolver
{
    protected Container $container;
    protected BridgeCache $cache;
    protected array $config;

    public function __construct(Container $container, BridgeCache $cache = null, array $config = [])
    {
        $this->container = $container;
        $this->cache = $cache ?? new BridgeCache();
        $this->config = $config;
    }

    public function resolve(string $service, array $context = []): mixed
    {
        return $this->container->make($service);
    }

    public function canResolve(string $service, array $context = []): bool
    {
        return $this->container->bound($service) || class_exists($service);
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
