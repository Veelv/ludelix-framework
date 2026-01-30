<?php

namespace Ludelix\Database\Events;

/**
 * Event fired before an entity is persisted (inserted) to the database.
 */
class PrePersistEvent extends EntityEvent
{
    /**
     * @param object $entity The entity about to be persisted.
     * @param array  $data   Additional data.
     */
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('pre_persist', $entity, $data);
    }
}