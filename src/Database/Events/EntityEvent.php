<?php

namespace Ludelix\Database\Events;

/**
 * Base class for database entity events.
 *
 * Wraps the entity involved in the event and any auxiliary data.
 */
class EntityEvent
{
    protected string $eventName;
    protected ?object $entity;
    protected array $data;

    /**
     * @param string      $eventName Name of the event.
     * @param object|null $entity    The entity instance.
     * @param array       $data      Additional context data.
     */
    public function __construct(string $eventName, ?object $entity, array $data = [])
    {
        $this->eventName = $eventName;
        $this->entity = $entity;
        $this->data = $data;
    }

    /**
     * Gets the event name.
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Gets the entity associated with the event.
     * @return object|null
     */
    public function getEntity(): ?object
    {
        return $this->entity;
    }

    /**
     * Gets additional event data.
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}