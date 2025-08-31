<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany
{
    public function __construct(
        public string $target,
        public string $mappedBy,
        public bool $cascade = false,
        public bool $eager = false,
        public ?string $orderBy = null,
        public array $where = []
    ) {}
}