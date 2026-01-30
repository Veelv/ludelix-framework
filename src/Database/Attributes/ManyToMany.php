<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Defines a Many-to-Many relationship.
 *
 * This attribute characterizes a relationship where many instances of this entity
 * are related to many instances of another entity, usually mediated by a join table.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToMany
{
    /**
     * @param string      $target            The FQN of the target entity class.
     * @param string|null $mappedBy          The field on the owning side (if this is the inverse side).
     * @param string|null $inversedBy        The field on the inverse side (if this is the owning side).
     * @param string|null $joinTable         The name of the join table.
     * @param string|null $joinColumn        The column name referencing this entity in the join table.
     * @param string|null $inverseJoinColumn The column name referencing the target entity in the join table.
     * @param bool        $cascade           Whether to cascade operations.
     * @param bool        $eager             Whether to eager load.
     */
    public function __construct(
        public string $target,
        public ?string $mappedBy = null,
        public ?string $inversedBy = null,
        public ?string $joinTable = null,
        public ?string $joinColumn = null,
        public ?string $inverseJoinColumn = null,
        public bool $cascade = false,
        public bool $eager = false
    ) {
    }
}