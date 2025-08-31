<?php

namespace Ludelix\Connect\Core;

use Ludelix\Connect\Events\ConnectInitializedEvent;
use Ludelix\Connect\Events\ComponentCacheEvent;
use Ludelix\Connect\Exceptions\ConnectException;
use Ludelix\Core\EventDispatcher;
use Ludelix\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Connect Manager - Core Connect System Management
 * 
 * Manages the lifecycle and configuration of the LudelixConnect system,
 * providing centralized control over component resolution, caching strategies,
 * performance optimization, and system-wide Connect operations.
 * 
 * Responsibilities:
 * - Component registry and lifecycle management
 * - Performance monitoring and optimization
 * - Cache strategy coordination
 * - System health monitoring
 * - Configuration management
 * - Event coordination
 * 
 * @package Ludelix\Connect\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class ConnectManager
{
    protected EventDispatcher $eventDispatcher;
    protected CacheManager $cache;
    protected LoggerInterface $logger;
    protected array $config;
    
    protected array $componentRegistry = [];
    protected array $componentMetadata = [];
    protected array $resolverPaths = [];
    protected array $performanceMetrics = [];
    protected int $totalRenders = 0;
    protected float $totalRenderTime = 0;
    protected bool $cacheEnabled = true;
    protected int $cacheTTL = 3600;

    public function __construct(
        EventDispatcher $eventDispatcher,
        CacheManager $cache,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
        
        $this->initializeConfiguration();
        $this->registerDefaultResolvers();
        
        $this->eventDispatcher->dispatch(new ConnectInitializedEvent($this->config));
    }

    public function addResolverPath(string $path, array $options = []): void
    {
        $this->resolverPaths[] = [
            'path' => $path,
            'options' => $options,
            'priority' => $options['priority'] ?? 0,
        ];
        
        usort($this->resolverPaths, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public function registerComponent(string $name, array $metadata): void
    {
        $this->componentRegistry[$name] = true;
        $this->componentMetadata[$name] = array_merge([
            'ssr_enabled' => true,
            'cache_ttl' => $this->cacheTTL,
            'preload' => false,
            'lazy' => true,
        ], $metadata);
    }

    public function getComponentMetadata(string $name): array
    {
        return $this->componentMetadata[$name] ?? [];
    }

    public function hasComponent(string $name): bool
    {
        return isset($this->componentRegistry[$name]);
    }

    public function getResolverPaths(): array
    {
        return $this->resolverPaths;
    }

    public function recordRender(string $component, float $duration, array $metadata = []): void
    {
        $this->totalRenders++;
        $this->totalRenderTime += $duration;
        
        $this->performanceMetrics[] = [
            'component' => $component,
            'duration' => $duration,
            'memory' => memory_get_usage(true),
            'timestamp' => microtime(true),
            'metadata' => $metadata,
        ];
        
        $this->eventDispatcher->dispatch(new ComponentCacheEvent(
            $component,
            $duration,
            $metadata
        ));
    }

    public function getPerformanceMetrics(): array
    {
        return [
            'total_renders' => $this->totalRenders,
            'total_render_time' => $this->totalRenderTime,
            'average_render_time' => $this->totalRenders > 0 ? $this->totalRenderTime / $this->totalRenders : 0,
            'renders' => $this->performanceMetrics,
        ];
    }

    public function clearCache(string $component = null): bool
    {
        if ($component) {
            return $this->cache->forget("connect.component.{$component}");
        }
        
        return $this->cache->flush();
    }

    protected function initializeConfiguration(): void
    {
        $this->cacheEnabled = $this->config['cache']['enabled'] ?? true;
        $this->cacheTTL = $this->config['cache']['ttl'] ?? 3600;
    }

    protected function registerDefaultResolvers(): void
    {
        $defaultPaths = [
            'frontend/js/pages',
            'frontend/js/components',
            'resources/js/pages',
            'resources/js/components',
        ];
        
        foreach ($defaultPaths as $path) {
            $this->addResolverPath($path, ['priority' => 0]);
        }
    }
}