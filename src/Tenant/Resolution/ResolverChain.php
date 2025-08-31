<?php

namespace Ludelix\Tenant\Resolution;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\PRT\Request;

/**
 * Resolver Chain - Chain of Responsibility Pattern for Tenant Resolution
 * 
 * Implements chain of responsibility pattern for tenant resolution strategies,
 * allowing flexible ordering and fallback mechanisms for tenant identification.
 * 
 * @package Ludelix\Tenant\Resolution
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ResolverChain
{
    /**
     * Chain of resolvers
     */
    protected array $resolvers = [];

    /**
     * Chain configuration
     */
    protected array $config;

    /**
     * Resolution cache
     */
    protected array $cache = [];

    /**
     * Initialize resolver chain
     * 
     * @param array $config Chain configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_enabled' => true,
            'fail_fast' => false,
            'log_attempts' => false,
        ], $config);
    }

    /**
     * Add resolver to chain
     * 
     * @param string $name Resolver name
     * @param object $resolver Resolver instance
     * @param int $priority Priority (higher = earlier in chain)
     * @return self Fluent interface
     */
    public function addResolver(string $name, object $resolver, int $priority = 0): self
    {
        $this->resolvers[] = [
            'name' => $name,
            'resolver' => $resolver,
            'priority' => $priority,
            'enabled' => true,
        ];

        // Sort by priority
        usort($this->resolvers, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $this;
    }

    /**
     * Remove resolver from chain
     * 
     * @param string $name Resolver name
     * @return self Fluent interface
     */
    public function removeResolver(string $name): self
    {
        $this->resolvers = array_filter(
            $this->resolvers,
            fn($resolver) => $resolver['name'] !== $name
        );

        return $this;
    }

    /**
     * Enable/disable specific resolver
     * 
     * @param string $name Resolver name
     * @param bool $enabled Enable status
     * @return self Fluent interface
     */
    public function setResolverEnabled(string $name, bool $enabled): self
    {
        foreach ($this->resolvers as &$resolver) {
            if ($resolver['name'] === $name) {
                $resolver['enabled'] = $enabled;
                break;
            }
        }

        return $this;
    }

    /**
     * Resolve tenant using chain of resolvers
     * 
     * @param Request $request HTTP request
     * @param array $options Resolution options
     * @return TenantInterface|null Resolved tenant or null
     */
    public function resolve(Request $request, array $options = []): ?TenantInterface
    {
        $cacheKey = $this->generateCacheKey($request, $options);

        // Check cache if enabled
        if ($this->config['cache_enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $attempts = [];
        $tenant = null;

        // Try each resolver in priority order
        foreach ($this->resolvers as $resolverConfig) {
            if (!$resolverConfig['enabled']) {
                continue;
            }

            $resolverName = $resolverConfig['name'];
            $resolver = $resolverConfig['resolver'];

            try {
                $startTime = microtime(true);
                $resolvedTenant = $resolver->resolve($request, $options);
                $duration = microtime(true) - $startTime;

                $attempts[] = [
                    'resolver' => $resolverName,
                    'success' => $resolvedTenant !== null,
                    'duration' => $duration,
                    'error' => null,
                ];

                if ($resolvedTenant instanceof TenantInterface) {
                    $tenant = $resolvedTenant;
                    break;
                }

            } catch (\Throwable $e) {
                $attempts[] = [
                    'resolver' => $resolverName,
                    'success' => false,
                    'duration' => microtime(true) - $startTime,
                    'error' => $e->getMessage(),
                ];

                if ($this->config['fail_fast']) {
                    throw $e;
                }
            }
        }

        // Log resolution attempts if configured
        if ($this->config['log_attempts']) {
            $this->logResolutionAttempts($request, $attempts, $tenant);
        }

        // Cache result if enabled
        if ($this->config['cache_enabled'] && $tenant) {
            $this->cache[$cacheKey] = $tenant;
        }

        return $tenant;
    }

    /**
     * Get chain statistics
     * 
     * @return array Chain statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_resolvers' => count($this->resolvers),
            'enabled_resolvers' => count(array_filter($this->resolvers, fn($r) => $r['enabled'])),
            'resolver_list' => array_map(fn($r) => [
                'name' => $r['name'],
                'priority' => $r['priority'],
                'enabled' => $r['enabled'],
            ], $this->resolvers),
            'cache_size' => count($this->cache),
        ];
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
     * Get resolver by name
     * 
     * @param string $name Resolver name
     * @return object|null Resolver instance or null
     */
    public function getResolver(string $name): ?object
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver['name'] === $name) {
                return $resolver['resolver'];
            }
        }

        return null;
    }

    /**
     * Check if resolver exists in chain
     * 
     * @param string $name Resolver name
     * @return bool True if resolver exists
     */
    public function hasResolver(string $name): bool
    {
        return $this->getResolver($name) !== null;
    }

    /**
     * Generate cache key for request
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
            'options' => $options,
        ];

        return md5(serialize($keyData));
    }

    /**
     * Log resolution attempts
     * 
     * @param Request $request HTTP request
     * @param array $attempts Resolution attempts
     * @param TenantInterface|null $tenant Resolved tenant
     */
    protected function logResolutionAttempts(Request $request, array $attempts, ?TenantInterface $tenant): void
    {
        $logData = [
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'request_uri' => $request->getUri(),
            'request_method' => $request->getMethod(),
            'resolved_tenant' => $tenant?->getId(),
            'total_attempts' => count($attempts),
            'successful_attempts' => count(array_filter($attempts, fn($a) => $a['success'])),
            'total_duration' => array_sum(array_column($attempts, 'duration')),
            'attempts' => $attempts,
        ];

        // This would integrate with logging system
        error_log('Tenant Resolution Chain: ' . json_encode($logData));
    }
}