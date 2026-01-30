<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Automatically trims whitespace from string properties.
 *
 * This attribute instructs the EntityProcessor to trim the property value
 * before persistence, ensuring data cleanliness.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoTrim
{
    /**
     * @param bool $enabled Whether the trimming behavior is active.
     */
    public function __construct(
        public bool $enabled = true
    ) {
    }
}