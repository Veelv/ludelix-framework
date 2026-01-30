<?php

namespace Ludelix\Bridge\Exceptions;

/**
 * Circular Dependency Exception
 * 
 * Thrown when a circular dependency is detected during service resolution.
 * Provides detailed information about the dependency chain that caused the cycle.
 * 
 * @package Ludelix\Bridge\Exceptions
 * @author Ludelix Framework Team
 */
class CircularDependencyException extends BridgeException
{
    protected array $dependencyChain = [];

    public function __construct(
        string $message = "",
        array $dependencyChain = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->dependencyChain = $dependencyChain;
        
        if (empty($message) && !empty($dependencyChain)) {
            $message = "Circular dependency detected: " . implode(' -> ', $dependencyChain);
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getDependencyChain(): array
    {
        return $this->dependencyChain;
    }

    public function getCircularPath(): string
    {
        return implode(' -> ', $this->dependencyChain);
    }
}