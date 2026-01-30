<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Route Compilation Exception
 * 
 * Thrown when route compilation fails.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class RouteCompilationException extends RoutingException
{
    protected string $routePath;

    public function __construct(string $message, string $routePath = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->routePath = $routePath;
    }

    public function getRoutePath(): string
    {
        return $this->routePath;
    }
}