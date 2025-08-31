<?php

namespace Ludelix\Database\Events;

class PostLoadEvent extends EntityEvent
{
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('post_load', $entity, $data);
    }
}