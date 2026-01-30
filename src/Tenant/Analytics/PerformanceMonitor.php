<?php

namespace Ludelix\Tenant\Analytics;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Performance Monitor - Tenant Performance Monitoring System
 * 
 * Monitors and analyzes tenant performance metrics including response times,
 * throughput, error rates, and system resource utilization.
 * 
 * @package Ludelix\Tenant\Analytics
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class PerformanceMonitor
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Performance data storage
     */
    protected array $performanceData = [];

    /**
     * Active measurements
     */
    protected array $activeMeasurements = [];

    /**
     * Monitor configuration
     */
    protected array $config;

    /**
     * Performance thresholds
     */
    protected array $thresholds = [
        'response_time' => 1000, // ms
        'error_rate' => 5, // %
        'memory_usage' => 128, // MB
        'cpu_usage' => 80, // %
    ];

    /**
     * Initialize performance monitor
     * 
     * @param array $config Monitor configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'storage_path' => 'storage/performance',
            'sample_rate' => 1.0,
            'buffer_size' => 1000,
            'alert_thresholds' => $this->thresholds,
        ], $config);
        
        $this->thresholds = array_merge($this->thresholds, $this->config['alert_thresholds']);
        $this->ensureStorageDirectory();
    }

    /**
     * Set current tenant context
     * 
     * @param TenantInterface $tenant Current tenant
     * @return self Fluent interface
     */
    public function setCurrentTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        return $this;
    }

    /**
     * Start performance measurement
     * 
     * @param string $operation Operation name
     * @param array $context Additional context
     * @return string Measurement ID
     */
    public function startMeasurement(string $operation, array $context = []): string
    {
        $measurementId = uniqid('perf_', true);
        
        $this->activeMeasurements[$measurementId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'tenant_id' => $this->currentTenant?->getId(),
        ];

        return $measurementId;
    }

    /**
     * End performance measurement
     * 
     * @param string $measurementId Measurement ID
     * @param array $additionalData Additional data
     * @return array Performance data
     */
    public function endMeasurement(string $measurementId, array $additionalData = []): array
    {
        if (!isset($this->activeMeasurements[$measurementId])) {
            return [];
        }

        $measurement = $this->activeMeasurements[$measurementId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $performanceData = [
            'measurement_id' => $measurementId,
            'operation' => $measurement['operation'],
            'tenant_id' => $measurement['tenant_id'],
            'start_time' => $measurement['start_time'],
            'end_time' => $endTime,
            'duration' => ($endTime - $measurement['start_time']) * 1000, // ms
            'memory_start' => $measurement['start_memory'],
            'memory_end' => $endMemory,
            'memory_used' => $endMemory - $measurement['start_memory'],
            'context' => $measurement['context'],
            'additional_data' => $additionalData,
            'timestamp' => $endTime,
            'datetime' => date('Y-m-d H:i:s'),
        ];

        // Store performance data
        $this->storePerformanceData($performanceData);

        // Check for performance alerts
        $this->checkPerformanceAlerts($performanceData);

        // Clean up active measurement
        unset($this->activeMeasurements[$measurementId]);

        return $performanceData;
    }

    /**
     * Record HTTP request performance
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param int $statusCode Response status code
     * @param float $responseTime Response time in seconds
     * @param array $context Additional context
     * @return self Fluent interface
     */
    public function recordHttpRequest(string $method, string $uri, int $statusCode, float $responseTime, array $context = []): self
    {
        $data = [
            'type' => 'http_request',
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'response_time' => $responseTime * 1000, // Convert to ms
            'is_error' => $statusCode >= 400,
            'context' => $context,
            'tenant_id' => $this->currentTenant?->getId(),
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
        ];

        $this->storePerformanceData($data);
        return $this;
    }

    /**
     * Record database query performance
     * 
     * @param string $query Query type
     * @param float $executionTime Execution time in seconds
     * @param int $affectedRows Affected rows
     * @param array $context Additional context
     * @return self Fluent interface
     */
    public function recordDatabaseQuery(string $query, float $executionTime, int $affectedRows = 0, array $context = []): self
    {
        $data = [
            'type' => 'database_query',
            'query_type' => $query,
            'execution_time' => $executionTime * 1000, // Convert to ms
            'affected_rows' => $affectedRows,
            'is_slow' => $executionTime > 1.0, // Slow if > 1 second
            'context' => $context,
            'tenant_id' => $this->currentTenant?->getId(),
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
        ];

        $this->storePerformanceData($data);
        return $this;
    }

    /**
     * Record cache operation performance
     * 
     * @param string $operation Cache operation
     * @param bool $hit Cache hit/miss
     * @param float $responseTime Response time in seconds
     * @param array $context Additional context
     * @return self Fluent interface
     */
    public function recordCacheOperation(string $operation, bool $hit, float $responseTime, array $context = []): self
    {
        $data = [
            'type' => 'cache_operation',
            'operation' => $operation,
            'hit' => $hit,
            'response_time' => $responseTime * 1000, // Convert to ms
            'context' => $context,
            'tenant_id' => $this->currentTenant?->getId(),
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
        ];

        $this->storePerformanceData($data);
        return $this;
    }

    /**
     * Get performance metrics for tenant
     * 
     * @param string|null $tenantId Tenant ID
     * @param array $filters Metric filters
     * @return array Performance metrics
     */
    public function getMetrics(?string $tenantId = null, array $filters = []): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }

        $performanceFile = $this->getPerformanceFile($targetTenantId);
        $data = $this->loadPerformanceData($performanceFile);

        // Apply filters
        if (!empty($filters)) {
            $data = array_filter($data, function($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (!isset($item[$key]) || $item[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        return $this->calculateMetrics($data);
    }

    /**
     * Get performance summary
     * 
     * @param string|null $tenantId Tenant ID
     * @param string $period Time period
     * @return array Performance summary
     */
    public function getSummary(?string $tenantId = null, string $period = 'last_24h'): array
    {
        $data = $this->getPerformanceData($tenantId, $period);
        
        return [
            'overview' => $this->calculateOverviewMetrics($data),
            'http_requests' => $this->calculateHttpMetrics($data),
            'database_queries' => $this->calculateDatabaseMetrics($data),
            'cache_operations' => $this->calculateCacheMetrics($data),
            'alerts' => $this->getActiveAlerts($tenantId),
        ];
    }

    /**
     * Get performance trends
     * 
     * @param string|null $tenantId Tenant ID
     * @param string $metric Metric name
     * @param int $hours Number of hours
     * @return array Trend data
     */
    public function getTrends(?string $tenantId = null, string $metric = 'response_time', int $hours = 24): array
    {
        $data = $this->getPerformanceData($tenantId, "last_{$hours}h");
        $trends = [];
        
        foreach ($data as $item) {
            $hour = date('Y-m-d H:00', $item['timestamp']);
            
            if (!isset($trends[$hour])) {
                $trends[$hour] = ['count' => 0, 'total' => 0, 'avg' => 0];
            }
            
            if (isset($item[$metric])) {
                $trends[$hour]['count']++;
                $trends[$hour]['total'] += $item[$metric];
                $trends[$hour]['avg'] = $trends[$hour]['total'] / $trends[$hour]['count'];
            }
        }

        ksort($trends);
        return $trends;
    }

    /**
     * Store performance data
     * 
     * @param array $data Performance data
     */
    protected function storePerformanceData(array $data): void
    {
        if (!$this->shouldSample()) {
            return;
        }

        $tenantId = $data['tenant_id'] ?? 'system';
        
        if (!isset($this->performanceData[$tenantId])) {
            $this->performanceData[$tenantId] = [];
        }

        $this->performanceData[$tenantId][] = $data;

        // Flush if buffer is full
        if (count($this->performanceData[$tenantId]) >= $this->config['buffer_size']) {
            $this->flushPerformanceData($tenantId);
        }
    }

    /**
     * Check for performance alerts
     * 
     * @param array $data Performance data
     */
    protected function checkPerformanceAlerts(array $data): void
    {
        $alerts = [];

        // Check response time
        if (isset($data['duration']) && $data['duration'] > $this->thresholds['response_time']) {
            $alerts[] = [
                'type' => 'slow_response',
                'threshold' => $this->thresholds['response_time'],
                'actual' => $data['duration'],
                'operation' => $data['operation'] ?? 'unknown',
            ];
        }

        // Check memory usage
        if (isset($data['memory_used']) && $data['memory_used'] > ($this->thresholds['memory_usage'] * 1024 * 1024)) {
            $alerts[] = [
                'type' => 'high_memory',
                'threshold' => $this->thresholds['memory_usage'],
                'actual' => round($data['memory_used'] / 1024 / 1024, 2),
                'operation' => $data['operation'] ?? 'unknown',
            ];
        }

        // Store alerts if any
        if (!empty($alerts)) {
            $this->storeAlerts($data['tenant_id'] ?? 'system', $alerts);
        }
    }

    /**
     * Calculate performance metrics
     * 
     * @param array $data Performance data
     * @return array Calculated metrics
     */
    protected function calculateMetrics(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $responseTimes = array_column($data, 'response_time');
        $responseTimes = array_filter($responseTimes, 'is_numeric');

        return [
            'total_requests' => count($data),
            'avg_response_time' => !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0,
            'min_response_time' => !empty($responseTimes) ? min($responseTimes) : 0,
            'max_response_time' => !empty($responseTimes) ? max($responseTimes) : 0,
            'error_rate' => $this->calculateErrorRate($data),
            'throughput' => $this->calculateThroughput($data),
        ];
    }

    /**
     * Calculate error rate
     * 
     * @param array $data Performance data
     * @return float Error rate percentage
     */
    protected function calculateErrorRate(array $data): float
    {
        if (empty($data)) {
            return 0.0;
        }

        $errors = array_filter($data, fn($item) => $item['is_error'] ?? false);
        return (count($errors) / count($data)) * 100;
    }

    /**
     * Calculate throughput
     * 
     * @param array $data Performance data
     * @return float Requests per second
     */
    protected function calculateThroughput(array $data): float
    {
        if (empty($data)) {
            return 0.0;
        }

        $timestamps = array_column($data, 'timestamp');
        $timeSpan = max($timestamps) - min($timestamps);
        
        return $timeSpan > 0 ? count($data) / $timeSpan : 0.0;
    }

    /**
     * Get performance data for period
     * 
     * @param string|null $tenantId Tenant ID
     * @param string $period Time period
     * @return array Performance data
     */
    protected function getPerformanceData(?string $tenantId, string $period): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }

        $performanceFile = $this->getPerformanceFile($targetTenantId);
        $data = $this->loadPerformanceData($performanceFile);

        // Filter by period
        $cutoffTime = $this->getPeriodCutoff($period);
        
        return array_filter($data, fn($item) => $item['timestamp'] >= $cutoffTime);
    }

    /**
     * Get period cutoff timestamp
     * 
     * @param string $period Period string
     * @return float Cutoff timestamp
     */
    protected function getPeriodCutoff(string $period): float
    {
        return match($period) {
            'last_1h' => microtime(true) - 3600,
            'last_24h' => microtime(true) - 86400,
            'last_7d' => microtime(true) - 604800,
            'last_30d' => microtime(true) - 2592000,
            default => microtime(true) - 86400
        };
    }

    /**
     * Should sample this request
     * 
     * @return bool True if should sample
     */
    protected function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() <= $this->config['sample_rate'];
    }

    /**
     * Flush performance data to storage
     * 
     * @param string $tenantId Tenant ID
     */
    protected function flushPerformanceData(string $tenantId): void
    {
        if (!isset($this->performanceData[$tenantId])) {
            return;
        }

        $performanceFile = $this->getPerformanceFile($tenantId);
        $existingData = $this->loadPerformanceData($performanceFile);
        
        $mergedData = array_merge($existingData, $this->performanceData[$tenantId]);
        file_put_contents($performanceFile, json_encode($mergedData));

        unset($this->performanceData[$tenantId]);
    }

    /**
     * Load performance data from file
     * 
     * @param string $performanceFile Performance file path
     * @return array Performance data
     */
    protected function loadPerformanceData(string $performanceFile): array
    {
        if (!file_exists($performanceFile)) {
            return [];
        }

        $data = json_decode(file_get_contents($performanceFile), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Get performance file path
     * 
     * @param string $tenantId Tenant ID
     * @return string Performance file path
     */
    protected function getPerformanceFile(string $tenantId): string
    {
        return $this->config['storage_path'] . "/performance_{$tenantId}.json";
    }

    /**
     * Store performance alerts
     * 
     * @param string $tenantId Tenant ID
     * @param array $alerts Alert data
     */
    protected function storeAlerts(string $tenantId, array $alerts): void
    {
        // This would integrate with alerting system
        // For now, just log to file
        $alertFile = $this->config['storage_path'] . "/alerts_{$tenantId}.json";
        
        $existingAlerts = [];
        if (file_exists($alertFile)) {
            $existingAlerts = json_decode(file_get_contents($alertFile), true) ?: [];
        }

        foreach ($alerts as $alert) {
            $alert['timestamp'] = microtime(true);
            $alert['datetime'] = date('Y-m-d H:i:s');
            $existingAlerts[] = $alert;
        }

        file_put_contents($alertFile, json_encode($existingAlerts));
    }

    /**
     * Get active alerts for tenant
     * 
     * @param string|null $tenantId Tenant ID
     * @return array Active alerts
     */
    protected function getActiveAlerts(?string $tenantId): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }

        $alertFile = $this->config['storage_path'] . "/alerts_{$targetTenantId}.json";
        
        if (!file_exists($alertFile)) {
            return [];
        }

        $alerts = json_decode(file_get_contents($alertFile), true) ?: [];
        
        // Return only recent alerts (last hour)
        $cutoff = microtime(true) - 3600;
        return array_filter($alerts, fn($alert) => $alert['timestamp'] >= $cutoff);
    }

    /**
     * Calculate overview metrics
     * 
     * @param array $data Performance data
     * @return array Overview metrics
     */
    protected function calculateOverviewMetrics(array $data): array
    {
        return [
            'total_operations' => count($data),
            'avg_response_time' => $this->calculateAverageResponseTime($data),
            'error_rate' => $this->calculateErrorRate($data),
            'throughput' => $this->calculateThroughput($data),
        ];
    }

    /**
     * Calculate HTTP metrics
     * 
     * @param array $data Performance data
     * @return array HTTP metrics
     */
    protected function calculateHttpMetrics(array $data): array
    {
        $httpData = array_filter($data, fn($item) => ($item['type'] ?? '') === 'http_request');
        return $this->calculateMetrics($httpData);
    }

    /**
     * Calculate database metrics
     * 
     * @param array $data Performance data
     * @return array Database metrics
     */
    protected function calculateDatabaseMetrics(array $data): array
    {
        $dbData = array_filter($data, fn($item) => ($item['type'] ?? '') === 'database_query');
        return $this->calculateMetrics($dbData);
    }

    /**
     * Calculate cache metrics
     * 
     * @param array $data Performance data
     * @return array Cache metrics
     */
    protected function calculateCacheMetrics(array $data): array
    {
        $cacheData = array_filter($data, fn($item) => ($item['type'] ?? '') === 'cache_operation');
        $hits = array_filter($cacheData, fn($item) => $item['hit'] ?? false);
        
        return [
            'total_operations' => count($cacheData),
            'hit_rate' => count($cacheData) > 0 ? (count($hits) / count($cacheData)) * 100 : 0,
            'avg_response_time' => $this->calculateAverageResponseTime($cacheData),
        ];
    }

    /**
     * Calculate average response time
     * 
     * @param array $data Performance data
     * @return float Average response time
     */
    protected function calculateAverageResponseTime(array $data): float
    {
        $responseTimes = array_column($data, 'response_time');
        $responseTimes = array_filter($responseTimes, 'is_numeric');
        
        return !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0.0;
    }

    /**
     * Ensure storage directory exists
     */
    protected function ensureStorageDirectory(): void
    {
        if (!is_dir($this->config['storage_path'])) {
            mkdir($this->config['storage_path'], 0755, true);
        }
    }
}