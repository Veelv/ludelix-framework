<?php

namespace Ludelix\Connect\Events;

/**
 * Component Cache Event
 * 
 * Fired when component caching operations occur, providing performance
 * data for optimization and monitoring systems.
 * 
 * @package Ludelix\Connect\Events
 * @author Ludelix Framework Team
 */
class ComponentCacheEvent
{
    public function __construct(
        public readonly string $component,
        public readonly float $renderDuration,
        public readonly array $metadata = []
    ) {}
}