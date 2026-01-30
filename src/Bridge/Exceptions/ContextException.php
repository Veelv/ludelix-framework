<?php

namespace Ludelix\Bridge\Exceptions;

/**
 * Context Exception
 * 
 * Thrown when Bridge context operations fail or encounter invalid states.
 * Provides detailed error information for debugging complex context scenarios.
 * 
 * @package Ludelix\Bridge\Exceptions
 * @author Ludelix Framework Team
 */
class ContextException extends BridgeException
{
    protected array $contextData = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $contextData = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->contextData = $contextData;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}