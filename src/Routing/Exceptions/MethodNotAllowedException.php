<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Method Not Allowed Exception
 * 
 * Thrown when a route exists but the HTTP method is not allowed.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class MethodNotAllowedException extends RoutingException
{
    protected array $allowedMethods = [];

    public function __construct(string $message, array $allowedMethods = [], int $code = 405, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->allowedMethods = $allowedMethods;
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}