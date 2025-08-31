<?php

namespace Ludelix\Lifecycle\Events;

use Ludelix\PRT\Request;

/**
 * Before Bootstrap Event
 * 
 * Fired before application bootstrap
 */
class BeforeBootstrap
{
    public Request $request;
    public float $timestamp;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->timestamp = microtime(true);
    }

    /**
     * Get request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get timestamp
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
}