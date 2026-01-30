<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Enables lifecycle callbacks for an entity.
 *
 * This attribute marks the entity as one that has methods listening to
 * lifecycle events such as PrePersist, PostPersist, PreUpdate, etc.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class LifecycleCallbacks
{
    /**
     * @param array $callbacks Optional list of explicit callback mappings.
     */
    public function __construct(
        public array $callbacks = []
    ) {
    }
}