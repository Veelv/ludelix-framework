<?php

namespace Ludelix\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class PrimaryKey
{
    public function __construct(
        public bool $autoIncrement = false,
        public string $strategy = 'auto'
    ) {}
}