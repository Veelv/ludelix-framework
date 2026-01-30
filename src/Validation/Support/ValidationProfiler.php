<?php

namespace Ludelix\Validation\Support;

/**
 * ValidationProfiler - Performance profiler for validation
 * 
 * Provides performance profiling for validation operations
 */
class ValidationProfiler
{
    protected array $timers = [];
    protected array $metrics = [];
    protected bool $enabled = true;

    /**
     * Start a timer
     */
    public function start(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * End a timer
     */
    public function end(string $name): void
    {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return;
        }

        $timer = $this->timers[$name];
        $end = microtime(true);
        $memoryEnd = memory_get_usage(true);

        $this->metrics[$name] = [
            'duration' => ($end - $timer['start']) * 1000, // Convert to milliseconds
            'memory_usage' => $memoryEnd - $timer['memory_start'],
            'memory_peak' => memory_get_peak_usage(true),
            'start_time' => $timer['start'],
            'end_time' => $end,
        ];

        unset($this->timers[$name]);
    }

    /**
     * Get timer duration
     */
    public function getDuration(string $name): ?float
    {
        return $this->metrics[$name]['duration'] ?? null;
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(string $name): ?int
    {
        return $this->metrics[$name]['memory_usage'] ?? null;
    }

    /**
     * Get peak memory usage
     */
    public function getPeakMemoryUsage(string $name): ?int
    {
        return $this->metrics[$name]['memory_peak'] ?? null;
    }

    /**
     * Get all metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics for specific timer
     */
    public function getMetricsFor(string $name): ?array
    {
        return $this->metrics[$name] ?? null;
    }

    /**
     * Check if timer exists
     */
    public function hasTimer(string $name): bool
    {
        return isset($this->timers[$name]);
    }

    /**
     * Check if metrics exist for timer
     */
    public function hasMetrics(string $name): bool
    {
        return isset($this->metrics[$name]);
    }

    /**
     * Get active timers
     */
    public function getActiveTimers(): array
    {
        return array_keys($this->timers);
    }

    /**
     * Get completed timers
     */
    public function getCompletedTimers(): array
    {
        return array_keys($this->metrics);
    }

    /**
     * Enable/disable profiler
     */
    public function enable(bool $enabled = true): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Check if profiler is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Clear all metrics
     */
    public function clear(): void
    {
        $this->metrics = [];
        $this->timers = [];
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        if (empty($this->metrics)) {
            return [
                'total_timers' => 0,
                'total_duration' => 0,
                'average_duration' => 0,
                'total_memory' => 0,
                'peak_memory' => 0,
            ];
        }

        $totalDuration = 0;
        $totalMemory = 0;
        $peakMemory = 0;

        foreach ($this->metrics as $metric) {
            $totalDuration += $metric['duration'];
            $totalMemory += $metric['memory_usage'];
            $peakMemory = max($peakMemory, $metric['memory_peak']);
        }

        return [
            'total_timers' => count($this->metrics),
            'total_duration' => $totalDuration,
            'average_duration' => $totalDuration / count($this->metrics),
            'total_memory' => $totalMemory,
            'peak_memory' => $peakMemory,
        ];
    }

    /**
     * Get slowest timers
     */
    public function getSlowestTimers(int $limit = 10): array
    {
        $sorted = $this->metrics;
        uasort($sorted, function ($a, $b) {
            return $b['duration'] <=> $a['duration'];
        });

        return array_slice($sorted, 0, $limit, true);
    }

    /**
     * Get fastest timers
     */
    public function getFastestTimers(int $limit = 10): array
    {
        $sorted = $this->metrics;
        uasort($sorted, function ($a, $b) {
            return $a['duration'] <=> $b['duration'];
        });

        return array_slice($sorted, 0, $limit, true);
    }

    /**
     * Get memory usage statistics
     */
    public function getMemoryStats(): array
    {
        if (empty($this->metrics)) {
            return [
                'total_memory' => 0,
                'average_memory' => 0,
                'peak_memory' => 0,
                'memory_efficiency' => 0,
            ];
        }

        $totalMemory = 0;
        $peakMemory = 0;

        foreach ($this->metrics as $metric) {
            $totalMemory += $metric['memory_usage'];
            $peakMemory = max($peakMemory, $metric['memory_peak']);
        }

        return [
            'total_memory' => $totalMemory,
            'average_memory' => $totalMemory / count($this->metrics),
            'peak_memory' => $peakMemory,
            'memory_efficiency' => $totalMemory > 0 ? ($peakMemory / $totalMemory) * 100 : 0,
        ];
    }

    /**
     * Export profiler data
     */
    public function export(): array
    {
        return [
            'enabled' => $this->enabled,
            'active_timers' => $this->getActiveTimers(),
            'metrics' => $this->metrics,
            'summary' => $this->getSummary(),
            'memory_stats' => $this->getMemoryStats(),
        ];
    }
} 