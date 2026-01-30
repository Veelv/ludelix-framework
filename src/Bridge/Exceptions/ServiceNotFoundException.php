<?php

namespace Ludelix\Bridge\Exceptions;

/**
 * Service Not Found Exception
 * 
 * Thrown when a requested service cannot be resolved through the Bridge.
 * Provides detailed information about the failed service resolution attempt.
 * 
 * @package Ludelix\Bridge\Exceptions
 * @author Ludelix Framework Team
 */
class ServiceNotFoundException extends BridgeException
{
    protected string $serviceName;
    protected array $resolutionAttempts = [];

    public function __construct(
        string $serviceName,
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->serviceName = $serviceName;
        
        if (empty($message)) {
            $message = "Service '{$serviceName}' could not be resolved through Bridge";
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function addResolutionAttempt(string $method, string $result): self
    {
        $this->resolutionAttempts[] = [
            'method' => $method,
            'result' => $result,
            'timestamp' => microtime(true)
        ];
        
        return $this;
    }

    public function getResolutionAttempts(): array
    {
        return $this->resolutionAttempts;
    }
}