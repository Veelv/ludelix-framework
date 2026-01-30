<?php

namespace Ludelix\Routing\Events;

use Ludelix\Interface\Routing\RouteInterface;
use Ludelix\PRT\Request;

/**
 * Route Matched Event
 * 
 * Fired when a route is successfully matched to a request.
 * 
 * @package Ludelix\Routing\Events
 * @author Ludelix Framework Team
 */
class RouteMatchedEvent
{
    public function __construct(
        public readonly RouteInterface $route,
        public readonly Request $request,
        public readonly array $parameters
    ) {}
}