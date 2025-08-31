<?php

namespace Ludelix\Bridge\Events;

/**
 * Context Switch Event
 * 
 * Fired when the Bridge context is switched
 */
class ContextSwitchEvent
{
    public function __construct(
        public readonly array $previousContext,
        public readonly array $newContext
    ) {}
}