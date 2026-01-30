<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Defines a Many-to-One relationship.
 *
 * This attribute characterizes a relationship where the current entity
 * belongs to another entity (the "One" side). This side usually holds the foreign key.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne
{
    /**
     * @param string      $target           The Fully Qualified Name (FQN) of the target entity class.
     * @param string|null $inversedBy       The name of the property on the target entity that refers to this entity.
     * @param bool        $cascade          Whether to cascade persistence operations.
     * @param bool        $eager            Whether to eager load the related entity.
     * @param string|null $joinColumn       The name of the foreign key column in this entity's table.
     * @param string|null $referencedColumn The name of the referenced column in the target table (usually 'id').
     */
    public function __construct(
        public string $target,
        public ?string $inversedBy = null,
        public bool $cascade = false,
        public bool $eager = false,
        public ?string $joinColumn = null,
        public ?string $referencedColumn = null
    ) {
    }
}