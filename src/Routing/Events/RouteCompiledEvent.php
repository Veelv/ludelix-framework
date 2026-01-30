<?php

namespace Ludelix\Routing\Events;

/**
 * Route Compiled Event
 * 
 * Fired when routes are compiled for optimization.
 * 
 * @package Ludelix\Routing\Events
 * @author Ludelix Framework Team
 */
class RouteCompiledEvent
{
    public function __construct(
        public readonly int $routeCount,
        public readonly float $compilationTime
    ) {}
}