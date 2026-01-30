<?php

namespace Ludelix\Routing\Events;

use Ludelix\Interface\Routing\RouteInterface;

/**
 * Route Registered Event
 * 
 * Fired when a new route is registered in the router.
 * 
 * @package Ludelix\Routing\Events
 * @author Ludelix Framework Team
 */
class RouteRegisteredEvent
{
    public function __construct(
        public readonly RouteInterface $route
    ) {}
}