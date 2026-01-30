<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Base Routing Exception
 * 
 * Base exception class for all routing-related errors.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class RoutingException extends \Exception
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