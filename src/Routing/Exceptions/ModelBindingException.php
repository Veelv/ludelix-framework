<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Model Binding Exception
 * 
 * Thrown when model binding fails.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class ModelBindingException extends RoutingException
{
    protected string $parameter;
    protected mixed $value;

    public function __construct(string $message, string $parameter = '', mixed $value = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->parameter = $parameter;
        $this->value = $value;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}