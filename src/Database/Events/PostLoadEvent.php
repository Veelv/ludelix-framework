<?php

namespace Ludelix\Database\Events;

/**
 * Event fired after an entity has been loaded/hydrated from the database.
 */
class PostLoadEvent extends EntityEvent
{
    /**
     * @param object $entity The loaded entity.
     * @param array  $data   Additional data.
     */
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('post_load', $entity, $data);
    }
}