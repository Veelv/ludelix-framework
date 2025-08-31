<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany
{
    public function __construct(
        public string $target,
        public ?string $mappedBy = null,
        public ?string $inversedBy = null,
        public ?string $joinTable = null,
        public ?string $joinColumn = null,
        public ?string $inverseJoinColumn = null,
        public bool $cascade = false,
        public bool $eager = false
    ) {}
}