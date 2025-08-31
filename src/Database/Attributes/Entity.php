<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public string $table,
        public ?string $database = null,
        public ?string $schema = null,
        public array $indexes = [],
        public array $uniqueConstraints = [],
        public bool $readOnly = false,
        public ?string $repository = null
    ) {}
}