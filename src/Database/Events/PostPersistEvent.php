<?php

namespace Ludelix\Database\Events;

class PostPersistEvent extends EntityEvent
{
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('post_persist', $entity, $data);
    }
}