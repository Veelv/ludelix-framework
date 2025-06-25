<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public ?string $name = null,
        public string $type = 'string',
        public ?int $length = null,
        public bool $nullable = false,
        public mixed $default = null,
        public bool $unique = false,
        public ?string $comment = null,
        public array $options = []
    ) {}
}