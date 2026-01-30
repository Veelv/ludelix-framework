<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Route Not Found Exception
 * 
 * Thrown when no matching route is found for the given request.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class RouteNotFoundException extends RoutingException
{
    protected string $method;
    protected string $path;

    public function __construct(string $message, string $method = '', string $path = '', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->method = $method;
        $this->path = $path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}