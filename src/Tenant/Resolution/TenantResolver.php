<?php

namespace Ludelix\Tenant\Resolution;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Resolution\Strategies\DomainStrategy;
use Ludelix\Tenant\Resolution\Strategies\SubdomainStrategy;
use Ludelix\Tenant\Resolution\Strategies\HeaderStrategy;
use Ludelix\Tenant\Resolution\Strategies\PathStrategy;
use Ludelix\PRT\Request;

/**
 * Tenant Resolver - Multi-Strategy Tenant Resolution System
 * 
 * Orchestrates multiple tenant resolution strategies to identify the current
 * tenant from HTTP requests. Supports domain-based, subdomain, header-based,
 * and path-based resolution with configurable priority ordering.
 * 
 * @package Ludelix\Tenant\Resolution
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantResolver
{
    /**
     * Available resolution strategies
     */
    protected array $strategies = [];

    /**
     * Strategy execution order
     */
    protected array $strategyOrder = [
        'domain',
        'subdomain', 
        'header',
        'path'
    ];

    /**
     * Resolution cache for performance
     */
    protected array $cache = [];

    /**
     * Initialize resolver with default strategies
     */
    public function __construct()
    {
        $this->registerDefaultStrategies();
    }

    /**
     * Resolve tenant from HTTP request using configured strategies
     * 
     * @param Request $request HTTP request to analyze
     * @param array $options Resolution options and overrides
     * @return TenantInterface|null Resolved tenant or null if not found
     */
    public function resolve(Request $request, array $options = []): ?TenantInterface
    {
        $cacheKey = $this->generateCacheKey($request, $options);
        
        // Check cache first
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Get strategy order (allow override)
        $order = $options['strategy_order'] ?? $this->strategyOrder;
        
        // Try each strategy in order
        foreach ($order as $strategyName) {
            if (!isset($this->strategies[$strategyName])) {
                continue;
            }

            $strategy = $this->strategies[$strategyName];
            
            try {
                $tenant = $strategy->resolve($request, $options);
                
                if ($tenant instanceof TenantInterface) {
                    // Cache successful resolution
                    $this->cache[$cacheKey] = $tenant;
                    return $tenant;
                }
            } catch (\Throwable $e) {
                // Log strategy failure but continue to next strategy
                error_log("Tenant resolution strategy '{$strategyName}' failed: " . $e->getMessage());
                
                // If fail_fast is enabled, stop on first error
                if ($options['fail_fast'] ?? false) {
                    throw $e;
                }
            }
        }

        return null;
    }

    /**
     * Register custom resolution strategy
     * 
     * @param string $name Strategy name
     * @param object $strategy Strategy implementation
     * @return self Fluent interface
     */
    public function registerStrategy(string $name, object $strategy): self
    {
        $this->strategies[$name] = $strategy;
        return $this;
    }

    /**
     * Set strategy execution order
     * 
     * @param array $order Array of strategy names in execution order
     * @return self Fluent interface
     */
    public function setStrategyOrder(array $order): self
    {
        $this->strategyOrder = $order;
        return $this;
    }

    /**
     * Get available strategies
     * 
     * @return array Strategy names
     */
    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Clear resolution cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->cache = [];
        return $this;
    }

    /**
     * Register default resolution strategies
     */
    protected function registerDefaultStrategies(): void
    {
        $this->strategies = [
            'domain' => new DomainStrategy(),
            'subdomain' => new SubdomainStrategy(),
            'header' => new HeaderStrategy(),
            'path' => new PathStrategy(),
        ];
    }

    /**
     * Generate cache key for resolution result
     * 
     * @param Request $request HTTP request
     * @param array $options Resolution options
     * @return string Cache key
     */
    protected function generateCacheKey(Request $request, array $options): string
    {
        $keyData = [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'headers' => $request->getHeaders(),
            'options' => $options
        ];
        
        return md5(serialize($keyData));
    }
}