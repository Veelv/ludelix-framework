<?php

namespace Ludelix\Connect\Exceptions;

/**
 * Base Connect Exception
 * 
 * Base exception class for all LudelixConnect-related errors.
 * Provides common functionality and error context management.
 * 
 * @package Ludelix\Connect\Exceptions
 * @author Ludelix Framework Team
 */
class ConnectException extends \Exception
{
    protected array $context = [];

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}