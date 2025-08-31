<?php

namespace Ludelix\Core;

/**
 * Event Dispatcher - Advanced Event Management System
 * 
 * High-performance event dispatching system with support for
 * event listeners, subscribers, and middleware.
 * 
 * @package Ludelix\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 */
class EventDispatcher
{
    protected array $listeners = [];
    protected array $subscribers = [];

    public function dispatch(object $event): object
    {
        $eventClass = get_class($event);
        
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener($event);
            }
        }

        return $event;
    }

    public function listen(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function subscribe(object $subscriber): void
    {
        $this->subscribers[] = $subscriber;
        
        if (method_exists($subscriber, 'getSubscribedEvents')) {
            $events = $subscriber->getSubscribedEvents();
            
            foreach ($events as $eventClass => $method) {
                $this->listen($eventClass, [$subscriber, $method]);
            }
        }
    }

    public function getListeners(string $eventClass = null): array
    {
        if ($eventClass) {
            return $this->listeners[$eventClass] ?? [];
        }
        
        return $this->listeners;
    }

    public function hasListeners(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }

    public function removeListener(string $eventClass, callable $listener): void
    {
        if (isset($this->listeners[$eventClass])) {
            $key = array_search($listener, $this->listeners[$eventClass], true);
            if ($key !== false) {
                unset($this->listeners[$eventClass][$key]);
            }
        }
    }
}