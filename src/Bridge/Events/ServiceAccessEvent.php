<?php

namespace Ludelix\Bridge\Events;

/**
 * Service Access Event
 * 
 * Fired when a service is accessed through the Bridge
 */
class ServiceAccessEvent
{
    public function __construct(
        public readonly string $service,
        public readonly array $context,
        public readonly float $duration
    ) {}
}