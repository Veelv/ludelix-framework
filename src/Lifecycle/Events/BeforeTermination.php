<?php

namespace Ludelix\Lifecycle\Events;

use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Before Termination Event
 * 
 * Fired before request termination
 */
class BeforeTermination
{
    public Request $request;
    public Response $response;
    public float $timestamp;
    public float $executionTime;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->timestamp = microtime(true);
        $this->executionTime = $this->timestamp - ($_SERVER['REQUEST_TIME_FLOAT'] ?? $this->timestamp);
    }

    /**
     * Get request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Get execution time
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
}