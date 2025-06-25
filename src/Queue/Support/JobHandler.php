<?php

namespace Ludelix\Queue\Support;

use Ludelix\Queue\Core\Job;
use Ludelix\Queue\Core\QueueManager;

/**
 * Job Handler Helper
 * 
 * Helper functions for job handling
 */
class JobHandler
{
    protected static ?QueueManager $queueManager = null;

    /**
     * Set queue manager
     */
    public static function setQueueManager(QueueManager $manager): void
    {
        self::$queueManager = $manager;
    }

    /**
     * Dispatch job
     */
    public static function dispatch(Job $job, ?string $connection = null): string
    {
        if (!self::$queueManager) {
            throw new \RuntimeException('Queue manager not set');
        }
        
        return self::$queueManager->dispatch($job, $connection);
    }

    /**
     * Dispatch job with delay
     */
    public static function dispatchAfter(Job $job, int $delay, ?string $connection = null): string
    {
        $job->delay($delay);
        return self::dispatch($job, $connection);
    }

    /**
     * Dispatch job to specific queue
     */
    public static function dispatchTo(Job $job, string $queue, ?string $connection = null): string
    {
        $job->onQueue($queue);
        return self::dispatch($job, $connection);
    }
}