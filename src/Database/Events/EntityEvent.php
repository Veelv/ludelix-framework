<?php

namespace Ludelix\Database\Events;

class EntityEvent
{
    protected string $eventName;
    protected ?object $entity;
    protected array $data;
    
    public function __construct(string $eventName, ?object $entity, array $data = [])
    {
        $this->eventName = $eventName;
        $this->entity = $entity;
        $this->data = $data;
    }
    
    public function getEventName(): string { return $this->eventName; }
    public function getEntity(): ?object { return $this->entity; }
    public function getData(): array { return $this->data; }
}