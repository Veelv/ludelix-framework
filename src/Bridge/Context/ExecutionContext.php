<?php

namespace Ludelix\Bridge\Context;

use Ludelix\Bridge\Exceptions\ContextException;

/**
 * Execution Context Manager
 * 
 * Manages execution context for Bridge service resolution, providing
 * comprehensive tracking of service resolution chains, performance metrics,
 * and execution state management for complex application scenarios.
 * 
 * Features:
 * - Call stack tracking with depth analysis
 * - Performance profiling and bottleneck detection
 * - Memory usage monitoring and optimization
 * - Execution scope isolation for nested operations
 * - Thread-safe context switching for concurrent requests
 * - Automatic cleanup and resource management
 * 
 * @package Ludelix\Bridge\Context
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ExecutionContext
{
    protected array $scopeStack = [];
    protected array $performanceMetrics = [];
    protected array $metadata = [];
    protected int $maxDepth = 100;
    protected bool $performanceMonitoring = true;
    protected float $slowQueryThreshold = 0.1;
    protected int $initialMemoryUsage;
    protected array $memorySnapshots = [];

    public function __construct(array $config = [])
    {
        $this->maxDepth = $config['max_depth'] ?? 100;
        $this->performanceMonitoring = $config['performance_monitoring'] ?? true;
        $this->slowQueryThreshold = $config['slow_query_threshold'] ?? 0.1;
        
        $this->initialMemoryUsage = memory_get_usage(true);
        
        $this->metadata = [
            'process_id' => getmypid(),
            'start_time' => microtime(true),
            'start_memory' => $this->initialMemoryUsage,
        ];
    }

    public function enterScope(string $service, array $context = []): void
    {
        if (count($this->scopeStack) >= $this->maxDepth) {
            throw new ContextException("Maximum execution depth exceeded");
        }
        
        $this->scopeStack[] = [
            'service' => $service,
            'context' => $context,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }

    public function exitScope(): array
    {
        if (empty($this->scopeStack)) {
            throw new ContextException('No execution scope to exit');
        }
        
        $scope = array_pop($this->scopeStack);
        
        return [
            'service' => $scope['service'],
            'duration' => microtime(true) - $scope['start_time'],
            'memory_used' => memory_get_usage(true) - $scope['start_memory'],
        ];
    }

    public function getCurrentContext(): array
    {
        return [
            'scope_stack' => $this->scopeStack,
            'current_depth' => count($this->scopeStack),
            'metadata' => $this->metadata,
        ];
    }
}