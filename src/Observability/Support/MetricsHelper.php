<?php

namespace Ludelix\Observability\Support;

use Ludelix\Observability\Core\MetricsCollector;
use Ludelix\Observability\Core\TraceManager;
use Ludelix\Observability\Core\PerformanceMonitor;

/**
 * Metrics Helper
 * 
 * Helper functions for observability
 */
class MetricsHelper
{
    protected static ?MetricsCollector $metrics = null;
    protected static ?TraceManager $tracer = null;
    protected static ?PerformanceMonitor $monitor = null;

    /**
     * Set metrics collector
     */
    public static function setMetrics(MetricsCollector $metrics): void
    {
        self::$metrics = $metrics;
    }

    /**
     * Set trace manager
     */
    public static function setTracer(TraceManager $tracer): void
    {
        self::$tracer = $tracer;
    }

    /**
     * Set performance monitor
     */
    public static function setMonitor(PerformanceMonitor $monitor): void
    {
        self::$monitor = $monitor;
    }

    /**
     * Increment counter
     */
    public static function increment(string $name, array $labels = [], float $value = 1): void
    {
        self::$metrics?->increment($name, $labels, $value);
    }

    /**
     * Set gauge
     */
    public static function gauge(string $name, float $value, array $labels = []): void
    {
        self::$metrics?->gauge($name, $value, $labels);
    }

    /**
     * Record histogram
     */
    public static function histogram(string $name, float $value, array $labels = []): void
    {
        self::$metrics?->histogram($name, $value, $labels);
    }

    /**
     * Time execution
     */
    public static function time(string $name, callable $callback, array $labels = []): mixed
    {
        if (self::$metrics) {
            return self::$metrics->time($name, $callback, $labels);
        }
        
        return $callback();
    }

    /**
     * Start trace
     */
    public static function startTrace(string $operation, array $tags = []): string
    {
        return self::$tracer?->startTrace($operation, $tags) ?? '';
    }

    /**
     * Start span
     */
    public static function startSpan(string $operation, array $tags = []): string
    {
        return self::$tracer?->startSpan($operation, $tags) ?? '';
    }

    /**
     * Finish span
     */
    public static function finishSpan(string $spanId, array $tags = []): void
    {
        self::$tracer?->finishSpan($spanId, $tags);
    }

    /**
     * Trace execution
     */
    public static function trace(string $operation, callable $callback, array $tags = []): mixed
    {
        if (self::$tracer) {
            return self::$tracer->trace($operation, $callback, $tags);
        }
        
        return $callback();
    }

    /**
     * Start timer
     */
    public static function startTimer(string $name): void
    {
        self::$monitor?->startTimer($name);
    }

    /**
     * Stop timer
     */
    public static function stopTimer(string $name): float
    {
        return self::$monitor?->stopTimer($name) ?? 0.0;
    }

    /**
     * Measure execution
     */
    public static function measure(string $name, callable $callback): mixed
    {
        if (self::$monitor) {
            return self::$monitor->measure($name, $callback);
        }
        
        return $callback();
    }

    /**
     * Record memory checkpoint
     */
    public static function recordMemory(string $checkpoint): void
    {
        self::$monitor?->recordMemory($checkpoint);
    }

    /**
     * Get all metrics
     */
    public static function getMetrics(): array
    {
        return self::$metrics?->getMetrics() ?? [];
    }

    /**
     * Get traces
     */
    public static function getTraces(): array
    {
        return self::$tracer?->getTraces() ?? [];
    }

    /**
     * Get performance summary
     */
    public static function getPerformanceSummary(): array
    {
        return self::$monitor?->getSummary() ?? [];
    }

    /**
     * Get system metrics
     */
    public static function getSystemMetrics(): array
    {
        return self::$monitor?->getSystemMetrics() ?? [];
    }

    /**
     * Clear all metrics
     */
    public static function clear(): void
    {
        self::$metrics?->clear();
        self::$tracer?->clear();
        self::$monitor?->clear();
    }

    /**
     * Export metrics
     */
    public static function export(?string $driver = null): void
    {
        self::$metrics?->export($driver);
    }

    /**
     * Get observability dashboard data
     */
    public static function getDashboardData(): array
    {
        return [
            'metrics' => [
                'summary' => self::$metrics?->getSummary() ?? [],
                'data' => self::getMetrics()
            ],
            'traces' => [
                'summary' => self::$tracer?->getStats() ?? [],
                'data' => self::getTraces()
            ],
            'performance' => [
                'summary' => self::getPerformanceSummary(),
                'system' => self::getSystemMetrics()
            ],
            'timestamp' => time()
        ];
    }
}