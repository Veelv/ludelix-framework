<?php

namespace Ludelix\Bridge\Exceptions;

/**
 * Base Bridge Exception
 * 
 * Base exception class for all Bridge-related errors.
 * Provides common functionality and error handling patterns.
 * 
 * @package Ludelix\Bridge\Exceptions
 * @author Ludelix Framework Team
 */
class BridgeException extends \Exception
{
    protected array $errorContext = [];
    protected string $errorCode = '';

    public function setErrorContext(array $context): self
    {
        $this->errorContext = $context;
        return $this;
    }

    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    public function setErrorCode(string $code): self
    {
        $this->errorCode = $code;
        return $this;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}