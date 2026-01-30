<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Automatically generates a UUID for the property if one is not provided.
 *
 * This attribute instructs the EntityProcessor to generate a random UUID v4
 * and assign it to the property during the persistence lifecycle.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoUuid
{
    /**
     * @param bool $enabled Whether the UUID generation is active.
     */
    public function __construct(
        public bool $enabled = true
    ) {
    }
}