<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoUuid
{
    public function __construct(
        public bool $enabled = true
    ) {}
}