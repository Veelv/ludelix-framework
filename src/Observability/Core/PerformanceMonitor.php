<?php

namespace Ludelix\Observability\Core;

/**
 * Performance Monitor
 * 
 * Monitors application performance metrics
 */
class PerformanceMonitor
{
    protected array $timers = [];
    protected array $counters = [];
    protected array $memory = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'memory_tracking' => true,
            'auto_gc' => true
        ], $config);
    }

    /**
     * Start timer
     */
    public function startTimer(string $name): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => $this->config['memory_tracking'] ? memory_get_usage(true) : 0
        ];
    }

    /**
     * Stop timer
     */
    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $duration = microtime(true) - $this->timers[$name]['start'];
        $memoryUsed = $this->config['memory_tracking'] ? 
            memory_get_usage(true) - $this->timers[$name]['memory_start'] : 0;

        $this->timers[$name]['duration'] = $duration;
        $this->timers[$name]['memory_used'] = $memoryUsed;
        $this->timers[$name]['end'] = microtime(true);

        return $duration;
    }

    /**
     * Measure execution time
     */
    public function measure(string $name, callable $callback): mixed
    {
        $this->startTimer($name);
        
        try {
            $result = $callback();
            $this->stopTimer($name);
            return $result;
        } catch (\Throwable $e) {
            $this->stopTimer($name);
            throw $e;
        }
    }

    /**
     * Increment counter
     */
    public function increment(string $name, int $value = 1): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        
        $this->counters[$name] += $value;
    }

    /**
     * Record memory usage
     */
    public function recordMemory(string $checkpoint): void
    {
        if (!$this->config['memory_tracking']) {
            return;
        }

        $this->memory[$checkpoint] = [
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get timer results
     */
    public function getTimer(string $name): ?array
    {
        return $this->timers[$name] ?? null;
    }

    /**
     * Get all timers
     */
    public function getTimers(): array
    {
        return $this->timers;
    }

    /**
     * Get counter value
     */
    public function getCounter(string $name): int
    {
        return $this->counters[$name] ?? 0;
    }

    /**
     * Get all counters
     */
    public function getCounters(): array
    {
        return $this->counters;
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
            'checkpoints' => $this->memory
        ];
    }

    /**
     * Get system metrics
     */
    public function getSystemMetrics(): array
    {
        $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        
        return [
            'memory' => $this->getMemoryUsage(),
            'load_average' => $loadAvg,
            'cpu_count' => function_exists('nproc') ? (int)shell_exec('nproc') : 1,
            'uptime' => $this->getUptime(),
            'disk_usage' => $this->getDiskUsage()
        ];
    }

    /**
     * Get performance summary
     */
    public function getSummary(): array
    {
        $totalTime = 0;
        $completedTimers = 0;
        
        foreach ($this->timers as $timer) {
            if (isset($timer['duration'])) {
                $totalTime += $timer['duration'];
                $completedTimers++;
            }
        }
        
        return [
            'timers' => [
                'total' => count($this->timers),
                'completed' => $completedTimers,
                'total_time' => $totalTime,
                'average_time' => $completedTimers > 0 ? $totalTime / $completedTimers : 0
            ],
            'counters' => [
                'total' => count($this->counters),
                'sum' => array_sum($this->counters)
            ],
            'memory' => $this->getMemoryUsage(),
            'checkpoints' => count($this->memory)
        ];
    }

    /**
     * Clear all metrics
     */
    public function clear(): void
    {
        $this->timers = [];
        $this->counters = [];
        $this->memory = [];
    }

    /**
     * Force garbage collection
     */
    public function forceGC(): void
    {
        if ($this->config['auto_gc'] && function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Get uptime
     */
    protected function getUptime(): float
    {
        if (defined('LUDELIX_START_TIME')) {
            return microtime(true) - LUDELIX_START_TIME;
        }
        
        return 0.0;
    }

    /**
     * Get disk usage
     */
    protected function getDiskUsage(): array
    {
        $path = getcwd();
        
        return [
            'free' => disk_free_space($path),
            'total' => disk_total_space($path),
            'used_percent' => $this->calculateDiskUsagePercent($path)
        ];
    }

    /**
     * Calculate disk usage percentage
     */
    protected function calculateDiskUsagePercent(string $path): float
    {
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        
        if ($total === false || $free === false || $total === 0) {
            return 0.0;
        }
        
        return round((($total - $free) / $total) * 100, 2);
    }
}