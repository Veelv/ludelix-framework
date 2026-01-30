<?php

namespace Ludelix\Queue\Drivers;

/**
 * Database Queue Driver
 * 
 * Queue implementation using database
 */
class DatabaseQueue
{
    protected array $config;
    protected array $jobs = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Push job to queue
     */
    public function push(string $queue, array $payload, ?string $jobId = null): void
    {
        $this->jobs[] = [
            'id' => $jobId ?? uniqid(),
            'queue' => $queue,
            'payload' => $payload,
            'available_at' => time(),
            'created_at' => time()
        ];
    }

    /**
     * Push job with delay
     */
    public function later(int $delay, string $queue, array $payload, ?string $jobId = null): void
    {
        $this->jobs[] = [
            'id' => $jobId ?? uniqid(),
            'queue' => $queue,
            'payload' => $payload,
            'available_at' => time() + $delay,
            'created_at' => time()
        ];
    }

    /**
     * Pop job from queue
     */
    public function pop(string $queue): ?array
    {
        $now = time();
        
        foreach ($this->jobs as $index => $job) {
            if ($job['queue'] === $queue && $job['available_at'] <= $now) {
                unset($this->jobs[$index]);
                return $job['payload'];
            }
        }
        
        return null;
    }

    /**
     * Get queue size
     */
    public function size(string $queue): int
    {
        return count(array_filter($this->jobs, fn($job) => $job['queue'] === $queue));
    }

    /**
     * Clear queue
     */
    public function clear(string $queue): void
    {
        $this->jobs = array_filter($this->jobs, fn($job) => $job['queue'] !== $queue);
    }
}