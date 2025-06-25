<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoSlug
{
    public function __construct(
        public string $from,
        public bool $unique = true
    ) {}
}