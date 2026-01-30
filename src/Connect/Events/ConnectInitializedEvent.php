<?php

namespace Ludelix\Connect\Events;

/**
 * Connect Initialized Event
 * 
 * Fired when the LudelixConnect system is fully initialized and ready to handle requests.
 * Provides configuration data and system state for listeners.
 * 
 * @package Ludelix\Connect\Events
 * @author Ludelix Framework Team
 */
class ConnectInitializedEvent
{
    public function __construct(
        public readonly array $config,
        public readonly float $initializationTime = 0.0
    ) {}
}