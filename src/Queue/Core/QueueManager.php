<?php

namespace Ludelix\Queue\Core;

use Ludelix\Queue\Drivers\RedisQueue;
use Ludelix\Queue\Drivers\DatabaseQueue;

/**
 * Queue Manager
 * 
 * Manages queue connections and job dispatching
 */
class QueueManager
{
    protected array $connections = [];
    protected array $config;
    protected string $defaultConnection = 'redis';

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? 'redis';
    }

    /**
     * Dispatch job to queue
     */
    public function dispatch(Job $job, ?string $connection = null): string
    {
        $connection = $connection ?? $this->defaultConnection;
        $driver = $this->connection($connection);
        
        $jobId = $this->generateJobId();
        $payload = $this->createPayload($job, $jobId);
        
        if ($job->getDelay() > 0) {
            $driver->later($job->getDelay(), $job->getQueue(), $payload, $jobId);
        } else {
            $driver->push($job->getQueue(), $payload, $jobId);
        }
        
        return $jobId;
    }

    /**
     * Get queue connection
     */
    public function connection(?string $name = null)
    {
        $name = $name ?? $this->defaultConnection;
        
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }
        
        return $this->connections[$name];
    }

    /**
     * Create queue connection
     */
    protected function createConnection(string $name)
    {
        $config = $this->config['connections'][$name] ?? [];
        
        return match($config['driver'] ?? 'redis') {
            'redis' => new RedisQueue($config),
            'database' => new DatabaseQueue($config),
            default => throw new \InvalidArgumentException("Unsupported queue driver: {$config['driver']}")
        };
    }

    /**
     * Create job payload
     */
    protected function createPayload(Job $job, string $jobId): array
    {
        return [
            'id' => $jobId,
            'job' => get_class($job),
            'data' => $job->getData(),
            'maxTries' => $job->getMaxTries(),
            'timeout' => $job->getTimeout(),
            'attempts' => 0,
            'created_at' => time()
        ];
    }

    /**
     * Generate unique job ID
     */
    protected function generateJobId(): string
    {
        return uniqid('job_', true);
    }

    /**
     * Process next job from queue
     */
    public function work(string $queue = 'default', ?string $connection = null): bool
    {
        $connection = $connection ?? $this->defaultConnection;
        $driver = $this->connection($connection);
        
        $payload = $driver->pop($queue);
        
        if (!$payload) {
            return false;
        }
        
        return $this->processJob($payload);
    }

    /**
     * Process job payload
     */
    protected function processJob(array $payload): bool
    {
        try {
            $jobClass = $payload['job'];
            $job = new $jobClass();
            $job->setData($payload['data']);
            
            $job->handle();
            
            return true;
        } catch (\Throwable $e) {
            $this->handleFailedJob($payload, $e);
            return false;
        }
    }

    /**
     * Handle failed job
     */
    protected function handleFailedJob(array $payload, \Throwable $exception): void
    {
        $payload['attempts']++;
        
        if ($payload['attempts'] < $payload['maxTries']) {
            // Retry job
            $this->connection()->push($payload['queue'] ?? 'default', $payload);
        } else {
            // Job failed permanently
            $jobClass = $payload['job'];
            $job = new $jobClass();
            $job->setData($payload['data']);
            $job->failed($exception);
        }
    }
}