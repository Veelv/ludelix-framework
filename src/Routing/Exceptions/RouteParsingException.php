<?php

namespace Ludelix\Routing\Exceptions;

/**
 * Route Parsing Exception
 * 
 * Thrown when route parsing fails.
 * 
 * @package Ludelix\Routing\Exceptions
 * @author Ludelix Framework Team
 */
class RouteParsingException extends RoutingException
{
    protected string $parser;
    protected string $source;

    public function __construct(string $message, string $parser = '', string $source = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->parser = $parser;
        $this->source = $source;
    }

    public function getParser(): string
    {
        return $this->parser;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}