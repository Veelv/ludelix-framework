<?php

namespace Ludelix\Queue\Core;

/**
 * Queue Worker
 * 
 * Processes jobs from queues
 */
class Worker
{
    protected QueueManager $queueManager;
    protected bool $shouldQuit = false;
    protected int $maxJobs = 0;
    protected int $processedJobs = 0;

    public function __construct(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    /**
     * Start worker daemon
     */
    public function daemon(string $queue = 'default', array $options = []): void
    {
        $this->maxJobs = $options['max_jobs'] ?? 0;
        $sleep = $options['sleep'] ?? 3;
        $timeout = $options['timeout'] ?? 60;
        
        while (!$this->shouldQuit) {
            $jobProcessed = $this->queueManager->work($queue);
            
            if (!$jobProcessed) {
                sleep($sleep);
                continue;
            }
            
            $this->processedJobs++;
            
            if ($this->maxJobs > 0 && $this->processedJobs >= $this->maxJobs) {
                $this->stop();
            }
        }
    }

    /**
     * Process single job
     */
    public function runNextJob(string $queue = 'default'): bool
    {
        return $this->queueManager->work($queue);
    }

    /**
     * Stop worker
     */
    public function stop(): void
    {
        $this->shouldQuit = true;
    }

    /**
     * Get processed jobs count
     */
    public function getProcessedJobs(): int
    {
        return $this->processedJobs;
    }
}