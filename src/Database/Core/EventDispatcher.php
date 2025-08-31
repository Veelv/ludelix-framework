<?php

namespace Ludelix\Database\Core;

class EventDispatcher
{
    protected array $listeners = [];
    
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }
    
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