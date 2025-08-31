<?php

namespace Ludelix\Queue\Drivers;

/**
 * Redis Queue Driver
 * 
 * Queue implementation using Redis
 */
class RedisQueue
{
    protected array $config;
    protected array $queues = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Push job to queue
     */
    public function push(string $queue, array $payload, ?string $jobId = null): void
    {
        $this->queues[$queue][] = $payload;
    }

    /**
     * Push job with delay
     */
    public function later(int $delay, string $queue, array $payload, ?string $jobId = null): void
    {
        // Simulate delayed job
        $payload['delayed_until'] = time() + $delay;
        $this->queues[$queue][] = $payload;
    }

    /**
     * Pop job from queue
     */
    public function pop(string $queue): ?array
    {
        if (empty($this->queues[$queue])) {
            return null;
        }
        
        $job = array_shift($this->queues[$queue]);
        
        // Check if job is delayed
        if (isset($job['delayed_until']) && $job['delayed_until'] > time()) {
            // Put back delayed job
            array_unshift($this->queues[$queue], $job);
            return null;
        }
        
        return $job;
    }

    /**
     * Get queue size
     */
    public function size(string $queue): int
    {
        return count($this->queues[$queue] ?? []);
    }

    /**
     * Clear queue
     */
    public function clear(string $queue): void
    {
        unset($this->queues[$queue]);
    }
}