<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Marks a property as the primary key of the entity.
 *
 * This attribute identifies the unique identifier for the entity record.
 * It usually accompanies the #[Column] attribute.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PrimaryKey
{
    /**
     * @param bool   $autoIncrement Whether the database handles ID generation (AUTO_INCREMENT).
     * @param string $strategy      The generation strategy ('auto', 'sequence', 'uuid', 'none').
     */
    public function __construct(
        public bool $autoIncrement = false,
        public string $strategy = 'auto'
    ) {
    }
}