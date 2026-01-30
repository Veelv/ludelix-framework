<?php

namespace Ludelix\Database\Core;

/**
 * Simple event dispatcher for database events.
 */
class EventDispatcher
{
    /** @var array Registered listeners */
    protected array $listeners = [];

    /**
     * Registers a listener for an event.
     *
     * @param string   $event
     * @param callable $listener
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatches an event to all registered listeners.
     *
     * @param string $event
     * @param mixed  $data
     */
    public function dispatch(string $event, mixed $data = null): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            $listener($data);
        }
    }
}