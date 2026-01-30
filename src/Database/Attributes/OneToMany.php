<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Defines a One-to-Many relationship.
 *
 * This attribute characterizes a relationship where the current entity
 * acts as the "One" side, owning a collection of "Many" related entities.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany
{
    /**
     * @param string      $target   The Fully Qualified Name (FQN) of the target entity class.
     * @param string      $mappedBy The name of the property on the target entity that maps back to this entity.
     * @param bool        $cascade  Whether to cascade persistence operations (persist, remove) to related entities.
     * @param bool        $eager    Whether to fetch unrelated entities immediately (Eager Loading).
     * @param string|null $orderBy  Ordering clause for the collection (e.g., 'name ASC').
     * @param array       $where    Additional filtering criteria for the relationship.
     */
    public function __construct(
        public string $target,
        public string $mappedBy,
        public bool $cascade = false,
        public bool $eager = false,
        public ?string $orderBy = null,
        public array $where = []
    ) {
    }
}