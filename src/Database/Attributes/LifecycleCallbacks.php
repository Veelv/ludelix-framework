<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class LifecycleCallbacks
{
    public function __construct(
        public array $callbacks = []
    ) {}
}