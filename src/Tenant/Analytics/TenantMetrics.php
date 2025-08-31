<?php

namespace Ludelix\Tenant\Analytics;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\PRT\Request;

/**
 * Tenant Metrics - Analytics and Performance Monitoring
 * 
 * Collects and analyzes tenant usage metrics, performance data,
 * and operational statistics for monitoring and optimization.
 * 
 * @package Ludelix\Tenant\Analytics
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantMetrics
{
    /**
     * Metrics storage
     */
    protected array $metrics = [];

    /**
     * Performance counters
     */
    protected array $counters = [
        'resolutions' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'tenant_switches' => 0,
    ];

    /**
     * Resolution timing data
     */
    protected array $resolutionTimes = [];

    /**
     * Tenant usage statistics
     */
    protected array $tenantStats = [];

    /**
     * Record tenant resolution event
     * 
     * @param TenantInterface $tenant Resolved tenant
     * @param Request $request Source request
     * @param array $strategies Applied strategies
     */
    public function recordResolution(TenantInterface $tenant, Request $request, array $strategies = []): void
    {
        $this->counters['resolutions']++;
        
        $this->metrics[] = [
            'type' => 'resolution',
            'tenant_id' => $tenant->getId(),
            'timestamp' => microtime(true),
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->server('HTTP_USER_AGENT'),
            'uri' => $request->getUri(),
            'strategies' => $strategies,
        ];

        // Update tenant-specific stats
        $this->updateTenantStats($tenant->getId(), 'resolutions');
    }

    /**
     * Record cache hit event
     * 
     * @param string $type Cache type
     */
    public function recordCacheHit(string $type): void
    {
        $this->counters['cache_hits']++;
        
        $this->metrics[] = [
            'type' => 'cache_hit',
            'cache_type' => $type,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Record cache miss event
     * 
     * @param string $type Cache type
     */
    public function recordCacheMiss(string $type): void
    {
        $this->counters['cache_misses']++;
        
        $this->metrics[] = [
            'type' => 'cache_miss',
            'cache_type' => $type,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Record tenant context switch
     * 
     * @param TenantInterface $tenant Target tenant
     */
    public function recordTenantSwitch(TenantInterface $tenant): void
    {
        $this->counters['tenant_switches']++;
        
        $this->metrics[] = [
            'type' => 'tenant_switch',
            'tenant_id' => $tenant->getId(),
            'timestamp' => microtime(true),
        ];

        $this->updateTenantStats($tenant->getId(), 'switches');
    }

    /**
     * Record resolution timing
     * 
     * @param string $tenantId Tenant identifier
     * @param float $duration Resolution duration in seconds
     */
    public function recordResolutionTiming(string $tenantId, float $duration): void
    {
        $this->resolutionTimes[] = [
            'tenant_id' => $tenantId,
            'duration' => $duration,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get performance summary
     * 
     * @return array Performance metrics
     */
    public function getPerformanceSummary(): array
    {
        $cacheTotal = $this->counters['cache_hits'] + $this->counters['cache_misses'];
        $cacheHitRatio = $cacheTotal > 0 ? $this->counters['cache_hits'] / $cacheTotal : 0;
        
        return [
            'total_resolutions' => $this->counters['resolutions'],
            'total_switches' => $this->counters['tenant_switches'],
            'cache_hit_ratio' => round($cacheHitRatio * 100, 2),
            'average_resolution_time' => $this->getAverageResolutionTime(),
            'active_tenants' => count($this->tenantStats),
            'top_tenants' => $this->getTopTenants(5),
        ];
    }

    /**
     * Get tenant-specific statistics
     * 
     * @param string $tenantId Tenant identifier
     * @return array Tenant statistics
     */
    public function getTenantStats(string $tenantId): array
    {
        return $this->tenantStats[$tenantId] ?? [
            'resolutions' => 0,
            'switches' => 0,
            'first_seen' => null,
            'last_seen' => null,
        ];
    }

    /**
     * Get all collected metrics
     * 
     * @param array $filters Optional filters
     * @return array Metrics data
     */
    public function getMetrics(array $filters = []): array
    {
        $metrics = $this->metrics;
        
        // Apply filters
        if (isset($filters['tenant_id'])) {
            $metrics = array_filter($metrics, fn($m) => 
                isset($m['tenant_id']) && $m['tenant_id'] === $filters['tenant_id']
            );
        }
        
        if (isset($filters['type'])) {
            $metrics = array_filter($metrics, fn($m) => $m['type'] === $filters['type']);
        }
        
        if (isset($filters['since'])) {
            $metrics = array_filter($metrics, fn($m) => $m['timestamp'] >= $filters['since']);
        }
        
        return array_values($metrics);
    }

    /**
     * Get performance counters
     * 
     * @return array Counter values
     */
    public function getCounters(): array
    {
        return $this->counters;
    }

    /**
     * Clear all metrics data
     * 
     * @return self Fluent interface
     */
    public function clearMetrics(): self
    {
        $this->metrics = [];
        $this->counters = array_fill_keys(array_keys($this->counters), 0);
        $this->resolutionTimes = [];
        $this->tenantStats = [];
        
        return $this;
    }

    /**
     * Export metrics to array format
     * 
     * @return array Complete metrics export
     */
    public function export(): array
    {
        return [
            'summary' => $this->getPerformanceSummary(),
            'counters' => $this->counters,
            'tenant_stats' => $this->tenantStats,
            'resolution_times' => $this->resolutionTimes,
            'raw_metrics' => $this->metrics,
            'exported_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Update tenant-specific statistics
     * 
     * @param string $tenantId Tenant identifier
     * @param string $metric Metric type
     */
    protected function updateTenantStats(string $tenantId, string $metric): void
    {
        if (!isset($this->tenantStats[$tenantId])) {
            $this->tenantStats[$tenantId] = [
                'resolutions' => 0,
                'switches' => 0,
                'first_seen' => microtime(true),
                'last_seen' => microtime(true),
            ];
        }

        $this->tenantStats[$tenantId][$metric]++;
        $this->tenantStats[$tenantId]['last_seen'] = microtime(true);
    }

    /**
     * Calculate average resolution time
     * 
     * @return float Average time in seconds
     */
    protected function getAverageResolutionTime(): float
    {
        if (empty($this->resolutionTimes)) {
            return 0.0;
        }

        $total = array_sum(array_column($this->resolutionTimes, 'duration'));
        return $total / count($this->resolutionTimes);
    }

    /**
     * Get top tenants by activity
     * 
     * @param int $limit Number of tenants to return
     * @return array Top tenants
     */
    protected function getTopTenants(int $limit): array
    {
        $tenants = $this->tenantStats;
        
        // Sort by total activity (resolutions + switches)
        uasort($tenants, function($a, $b) {
            $activityA = $a['resolutions'] + $a['switches'];
            $activityB = $b['resolutions'] + $b['switches'];
            return $activityB <=> $activityA;
        });
        
        return array_slice($tenants, 0, $limit, true);
    }
}