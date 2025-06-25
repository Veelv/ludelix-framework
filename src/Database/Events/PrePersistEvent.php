<?php

namespace Ludelix\Database\Events;

class PrePersistEvent extends EntityEvent
{
    public function __construct(object $entity, array $data = [])
    {
        parent::__construct('pre_persist', $entity, $data);
    }
}