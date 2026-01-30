<?php
namespace Ludelix\Core;

/**
 * Class Events
 *
 * Simple event dispatcher for registering and dispatching events in the application.
 */
class Events
{
    /**
     * Registered event listeners.
     *
     * @var array
     */
    protected array $listeners = [];

    /**
     * Register a listener for a given event.
     *
     * @param string $event
     * @param callable $listener
     * @return void
     */
    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param string $event
     * @param mixed ...$payload
     * @return void
     */
    public function dispatch(string $event, ...$payload): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener(...$payload);
        }
    }
}
