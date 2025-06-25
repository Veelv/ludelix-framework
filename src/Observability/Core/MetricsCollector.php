<?php

namespace Ludelix\Observability\Core;

/**
 * Metrics Collector
 * 
 * Collects and manages application metrics
 */
class MetricsCollector
{
    protected array $metrics = [];
    protected array $config;
    protected array $drivers = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'default_driver' => 'memory',
            'namespace' => 'ludelix',
            'flush_interval' => 60
        ], $config);
    }

    /**
     * Add driver
     */
    public function addDriver(string $name, $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    /**
     * Increment counter
     */
    public function increment(string $name, array $labels = [], float $value = 1): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $key = $this->buildKey($name, $labels);
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'counter',
                'name' => $name,
                'labels' => $labels,
                'value' => 0,
                'timestamp' => time()
            ];
        }
        
        $this->metrics[$key]['value'] += $value;
        $this->metrics[$key]['timestamp'] = time();
    }

    /**
     * Set gauge value
     */
    public function gauge(string $name, float $value, array $labels = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $key = $this->buildKey($name, $labels);
        
        $this->metrics[$key] = [
            'type' => 'gauge',
            'name' => $name,
            'labels' => $labels,
            'value' => $value,
            'timestamp' => time()
        ];
    }

    /**
     * Record histogram value
     */
    public function histogram(string $name, float $value, array $labels = []): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $key = $this->buildKey($name, $labels);
        
        if (!isset($this->metrics[$key])) {
            $this->metrics[$key] = [
                'type' => 'histogram',
                'name' => $name,
                'labels' => $labels,
                'values' => [],
                'count' => 0,
                'sum' => 0,
                'timestamp' => time()
            ];
        }
        
        $this->metrics[$key]['values'][] = $value;
        $this->metrics[$key]['count']++;
        $this->metrics[$key]['sum'] += $value;
        $this->metrics[$key]['timestamp'] = time();
    }

    /**
     * Time execution
     */
    public function time(string $name, callable $callback, array $labels = []): mixed
    {
        $start = microtime(true);
        
        try {
            $result = $callback();
            $this->histogram($name . '_duration_seconds', microtime(true) - $start, $labels);
            $this->increment($name . '_total', array_merge($labels, ['status' => 'success']));
            return $result;
        } catch (\Throwable $e) {
            $this->histogram($name . '_duration_seconds', microtime(true) - $start, $labels);
            $this->increment($name . '_total', array_merge($labels, ['status' => 'error']));
            throw $e;
        }
    }

    /**
     * Get all metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics by type
     */
    public function getMetricsByType(string $type): array
    {
        return array_filter($this->metrics, fn($metric) => $metric['type'] === $type);
    }

    /**
     * Clear metrics
     */
    public function clear(): void
    {
        $this->metrics = [];
    }

    /**
     * Export metrics to driver
     */
    public function export(?string $driver = null): void
    {
        $driver = $driver ?? $this->config['default_driver'];
        
        if (isset($this->drivers[$driver])) {
            $this->drivers[$driver]->export($this->metrics);
        }
    }

    /**
     * Build metric key
     */
    protected function buildKey(string $name, array $labels): string
    {
        $labelStr = '';
        if (!empty($labels)) {
            ksort($labels);
            $labelStr = '_' . md5(serialize($labels));
        }
        
        return $this->config['namespace'] . '_' . $name . $labelStr;
    }

    /**
     * Record HTTP request
     */
    public function recordHttpRequest(string $method, string $route, int $status, float $duration): void
    {
        $labels = [
            'method' => $method,
            'route' => $route,
            'status' => (string)$status
        ];
        
        $this->increment('http_requests_total', $labels);
        $this->histogram('http_request_duration_seconds', $duration, $labels);
    }

    /**
     * Record database query
     */
    public function recordDatabaseQuery(string $type, float $duration, bool $success = true): void
    {
        $labels = [
            'type' => $type,
            'status' => $success ? 'success' : 'error'
        ];
        
        $this->increment('database_queries_total', $labels);
        $this->histogram('database_query_duration_seconds', $duration, $labels);
    }

    /**
     * Record cache operation
     */
    public function recordCacheOperation(string $operation, bool $hit = true): void
    {
        $labels = [
            'operation' => $operation,
            'result' => $hit ? 'hit' : 'miss'
        ];
        
        $this->increment('cache_operations_total', $labels);
    }

    /**
     * Record queue job
     */
    public function recordQueueJob(string $job, float $duration, bool $success = true): void
    {
        $labels = [
            'job' => $job,
            'status' => $success ? 'success' : 'failed'
        ];
        
        $this->increment('queue_jobs_total', $labels);
        $this->histogram('queue_job_duration_seconds', $duration, $labels);
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $counters = $this->getMetricsByType('counter');
        $gauges = $this->getMetricsByType('gauge');
        $histograms = $this->getMetricsByType('histogram');
        
        return [
            'total_metrics' => count($this->metrics),
            'counters' => count($counters),
            'gauges' => count($gauges),
            'histograms' => count($histograms),
            'last_updated' => max(array_column($this->metrics, 'timestamp'))
        ];
    }
}