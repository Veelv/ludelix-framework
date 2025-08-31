<?php

namespace Ludelix\Connect\Exceptions;

/**
 * Component Not Found Exception
 * 
 * Thrown when a requested component cannot be resolved through any
 * registered resolver paths or component registry.
 * 
 * @package Ludelix\Connect\Exceptions
 * @author Ludelix Framework Team
 */
class ComponentNotFoundException extends ConnectException
{
    protected string $componentName;
    protected array $searchPaths = [];

    public function __construct(
        string $message,
        string $componentName = '',
        array $searchPaths = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->componentName = $componentName;
        $this->searchPaths = $searchPaths;
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }
}