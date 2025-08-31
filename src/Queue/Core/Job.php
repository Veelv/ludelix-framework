<?php

namespace Ludelix\Queue\Core;

/**
 * Base Job Class
 * 
 * Abstract base class for all queue jobs
 */
abstract class Job
{
    protected string $queue = 'default';
    protected int $delay = 0;
    protected int $maxTries = 3;
    protected int $timeout = 60;
    protected array $data = [];

    /**
     * Execute the job
     */
    abstract public function handle(): void;

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        // Override in child classes if needed
    }

    /**
     * Set job data
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get job data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set queue name
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Set delay
     */
    public function delay(int $seconds): self
    {
        $this->delay = $seconds;
        return $this;
    }

    /**
     * Get queue name
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Get delay
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Get max tries
     */
    public function getMaxTries(): int
    {
        return $this->maxTries;
    }

    /**
     * Get timeout
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}