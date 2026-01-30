<?php

namespace Ludelix\Observability\Core;

/**
 * Auto Tracing
 * 
 * Automatically instruments code for tracing
 */
class AutoTracing
{
    protected TraceManager $tracer;
    protected array $config;
    protected array $activeSpans = [];

    public function __construct(TraceManager $tracer, array $config = [])
    {
        $this->tracer = $tracer;
        $this->config = array_merge([
            'enabled' => true,
            'trace_requests' => true,
            'trace_database' => true,
            'trace_cache' => true,
            'trace_queue' => true,
            'trace_external' => true,
            'min_duration' => 0.001 // 1ms
        ], $config);
    }

    /**
     * Auto trace HTTP request
     */
    public function traceRequest(string $method, string $uri, callable $handler): mixed
    {
        if (!$this->config['enabled'] || !$this->config['trace_requests']) {
            return $handler();
        }

        $traceId = $this->tracer->startTrace('http_request', [
            'http.method' => $method,
            'http.url' => $uri,
            'component' => 'http'
        ]);

        try {
            $result = $handler();
            $this->tracer->finishTrace($traceId);
            return $result;
        } catch (\Throwable $e) {
            $this->tracer->finishTrace($traceId);
            throw $e;
        }
    }

    /**
     * Auto trace database operations
     */
    public function traceDatabase(string $query, string $connection, callable $executor): mixed
    {
        if (!$this->config['enabled'] || !$this->config['trace_database']) {
            return $executor();
        }

        $spanId = $this->tracer->startSpan('db.query', [
            'db.statement' => $this->sanitizeQuery($query),
            'db.connection' => $connection,
            'component' => 'database'
        ]);

        $start = microtime(true);
        
        try {
            $result = $executor();
            $duration = microtime(true) - $start;
            
            $this->tracer->finishSpan($spanId, [
                'db.duration' => $duration,
                'db.success' => true
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $start;
            
            $this->tracer->finishSpan($spanId, [
                'db.duration' => $duration,
                'db.success' => false,
                'error' => true,
                'error.message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Auto trace cache operations
     */
    public function traceCache(string $operation, string $key, callable $executor): mixed
    {
        if (!$this->config['enabled'] || !$this->config['trace_cache']) {
            return $executor();
        }

        $spanId = $this->tracer->startSpan('cache.' . $operation, [
            'cache.key' => $key,
            'cache.operation' => $operation,
            'component' => 'cache'
        ]);

        try {
            $result = $executor();
            
            $this->tracer->finishSpan($spanId, [
                'cache.hit' => $result !== null && $operation === 'get'
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->tracer->finishSpan($spanId, [
                'error' => true,
                'error.message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Auto trace queue jobs
     */
    public function traceQueueJob(string $jobClass, callable $executor): mixed
    {
        if (!$this->config['enabled'] || !$this->config['trace_queue']) {
            return $executor();
        }

        $spanId = $this->tracer->startSpan('queue.job', [
            'job.class' => $jobClass,
            'component' => 'queue'
        ]);

        try {
            $result = $executor();
            
            $this->tracer->finishSpan($spanId, [
                'job.success' => true
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->tracer->finishSpan($spanId, [
                'job.success' => false,
                'error' => true,
                'error.message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Auto trace external HTTP calls
     */
    public function traceExternalCall(string $url, string $method, callable $executor): mixed
    {
        if (!$this->config['enabled'] || !$this->config['trace_external']) {
            return $executor();
        }

        $spanId = $this->tracer->startSpan('http.client', [
            'http.url' => $url,
            'http.method' => $method,
            'component' => 'http_client'
        ]);

        try {
            $result = $executor();
            
            $this->tracer->finishSpan($spanId, [
                'http.status_code' => $result['status'] ?? 200
            ]);
            
            return $result;
        } catch (\Throwable $e) {
            $this->tracer->finishSpan($spanId, [
                'error' => true,
                'error.message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Auto trace method execution
     */
    public function traceMethod(object $instance, string $method, array $args, callable $executor): mixed
    {
        if (!$this->config['enabled']) {
            return $executor();
        }

        $className = get_class($instance);
        $spanId = $this->tracer->startSpan($className . '::' . $method, [
            'class' => $className,
            'method' => $method,
            'component' => 'method'
        ]);

        try {
            $result = $executor();
            $this->tracer->finishSpan($spanId);
            return $result;
        } catch (\Throwable $e) {
            $this->tracer->finishSpan($spanId, [
                'error' => true,
                'error.message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Enable auto tracing for class
     */
    public function instrumentClass(string $className): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        // In a real implementation, this would use reflection or bytecode manipulation
        // to automatically wrap method calls with tracing
        $this->activeSpans[$className] = true;
    }

    /**
     * Sanitize SQL query for tracing
     */
    protected function sanitizeQuery(string $query): string
    {
        // Remove sensitive data and limit length
        $query = preg_replace('/\b\d{4,}\b/', '?', $query); // Replace numbers
        $query = preg_replace("/'[^']*'/", '?', $query); // Replace strings
        
        return substr($query, 0, 200);
    }

    /**
     * Check if should trace based on duration
     */
    protected function shouldTrace(float $duration): bool
    {
        return $duration >= $this->config['min_duration'];
    }

    /**
     * Get tracing statistics
     */
    public function getStats(): array
    {
        return [
            'enabled' => $this->config['enabled'],
            'active_spans' => count($this->activeSpans),
            'config' => [
                'trace_requests' => $this->config['trace_requests'],
                'trace_database' => $this->config['trace_database'],
                'trace_cache' => $this->config['trace_cache'],
                'trace_queue' => $this->config['trace_queue'],
                'trace_external' => $this->config['trace_external']
            ]
        ];
    }
}