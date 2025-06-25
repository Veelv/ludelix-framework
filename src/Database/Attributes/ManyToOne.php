<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne
{
    public function __construct(
        public string $target,
        public ?string $inversedBy = null,
        public bool $cascade = false,
        public bool $eager = false,
        public ?string $joinColumn = null,
        public ?string $referencedColumn = null
    ) {}
}