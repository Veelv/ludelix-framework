<?php

namespace Ludelix\Database\Events;

/**
 * Event fired after an entity has been successfully persisted.
 */
class PostPersistEvent extends EntityEvent
{
    /**
     * @param object $entity The persisted entity.
     * @param array  $data   Additional data.
     */
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('post_persist', $entity, $data);
    }
}