<?php

namespace Ludelix\Auth\Core\Event;

/**
 * AuthEventDispatcher - Handles authentication-related events
 * 
 * This class provides a simple event dispatcher for authentication events,
 * allowing listeners to be registered and dispatched for various auth actions.
 * 
 * @package Ludelix\Auth\Core\Event
 */
class AuthEventDispatcher
{
    /**
     * Registered event listeners
     *
     * @var array
     */
    protected array $listeners = [];

    /**
     * Register a listener for an event
     *
     * @param string $event The event name
     * @param callable $listener The listener callback
     * @return void
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners
     *
     * @param string $event The event name
     * @param mixed $payload The event payload
     * @return void
     */
    public function dispatch(string $event, $payload = null): void
    {
        if (!empty($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($payload);
            }
        }
    }
}