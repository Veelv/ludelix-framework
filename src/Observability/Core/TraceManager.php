<?php

namespace Ludelix\Observability\Core;

/**
 * Trace Manager
 * 
 * Manages distributed tracing
 */
class TraceManager
{
    protected array $traces = [];
    protected ?string $currentTraceId = null;
    protected array $spans = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'sample_rate' => 1.0,
            'max_spans' => 1000
        ], $config);
    }

    /**
     * Start new trace
     */
    public function startTrace(string $operation, array $tags = []): string
    {
        if (!$this->config['enabled'] || !$this->shouldSample()) {
            return '';
        }

        $traceId = $this->generateTraceId();
        $this->currentTraceId = $traceId;
        
        $this->traces[$traceId] = [
            'trace_id' => $traceId,
            'operation' => $operation,
            'start_time' => microtime(true),
            'tags' => $tags,
            'spans' => []
        ];
        
        return $traceId;
    }

    /**
     * Start span
     */
    public function startSpan(string $operation, array $tags = [], ?string $parentSpanId = null): string
    {
        if (!$this->config['enabled'] || !$this->currentTraceId) {
            return '';
        }

        $spanId = $this->generateSpanId();
        
        $span = [
            'span_id' => $spanId,
            'trace_id' => $this->currentTraceId,
            'parent_span_id' => $parentSpanId,
            'operation' => $operation,
            'start_time' => microtime(true),
            'tags' => $tags,
            'logs' => []
        ];
        
        $this->spans[$spanId] = $span;
        $this->traces[$this->currentTraceId]['spans'][] = $spanId;
        
        return $spanId;
    }

    /**
     * Finish span
     */
    public function finishSpan(string $spanId, array $tags = []): void
    {
        if (!isset($this->spans[$spanId])) {
            return;
        }
        
        $this->spans[$spanId]['end_time'] = microtime(true);
        $this->spans[$spanId]['duration'] = $this->spans[$spanId]['end_time'] - $this->spans[$spanId]['start_time'];
        $this->spans[$spanId]['tags'] = array_merge($this->spans[$spanId]['tags'], $tags);
    }

    /**
     * Add log to span
     */
    public function logToSpan(string $spanId, string $message, array $fields = []): void
    {
        if (!isset($this->spans[$spanId])) {
            return;
        }
        
        $this->spans[$spanId]['logs'][] = [
            'timestamp' => microtime(true),
            'message' => $message,
            'fields' => $fields
        ];
    }

    /**
     * Finish trace
     */
    public function finishTrace(?string $traceId = null): void
    {
        $traceId = $traceId ?? $this->currentTraceId;
        
        if (!isset($this->traces[$traceId])) {
            return;
        }
        
        $this->traces[$traceId]['end_time'] = microtime(true);
        $this->traces[$traceId]['duration'] = $this->traces[$traceId]['end_time'] - $this->traces[$traceId]['start_time'];
        
        if ($traceId === $this->currentTraceId) {
            $this->currentTraceId = null;
        }
    }

    /**
     * Get trace
     */
    public function getTrace(string $traceId): ?array
    {
        return $this->traces[$traceId] ?? null;
    }

    /**
     * Get all traces
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    /**
     * Get span
     */
    public function getSpan(string $spanId): ?array
    {
        return $this->spans[$spanId] ?? null;
    }

    /**
     * Get current trace ID
     */
    public function getCurrentTraceId(): ?string
    {
        return $this->currentTraceId;
    }

    /**
     * Trace function execution
     */
    public function trace(string $operation, callable $callback, array $tags = []): mixed
    {
        $spanId = $this->startSpan($operation, $tags);
        
        try {
            $result = $callback();
            $this->finishSpan($spanId, ['status' => 'success']);
            return $result;
        } catch (\Throwable $e) {
            $this->finishSpan($spanId, [
                'status' => 'error',
                'error.message' => $e->getMessage(),
                'error.class' => get_class($e)
            ]);
            throw $e;
        }
    }

    /**
     * Clear traces
     */
    public function clear(): void
    {
        $this->traces = [];
        $this->spans = [];
        $this->currentTraceId = null;
    }

    /**
     * Generate trace ID
     */
    protected function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate span ID
     */
    protected function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Check if should sample
     */
    protected function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config['sample_rate'];
    }

    /**
     * Get trace statistics
     */
    public function getStats(): array
    {
        $totalDuration = 0;
        $completedTraces = 0;
        
        foreach ($this->traces as $trace) {
            if (isset($trace['duration'])) {
                $totalDuration += $trace['duration'];
                $completedTraces++;
            }
        }
        
        return [
            'total_traces' => count($this->traces),
            'completed_traces' => $completedTraces,
            'total_spans' => count($this->spans),
            'average_duration' => $completedTraces > 0 ? $totalDuration / $completedTraces : 0,
            'current_trace_id' => $this->currentTraceId
        ];
    }
}