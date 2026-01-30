<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Automatically generates a URL-friendly slug based on another property.
 *
 * This attribute triggers the slug generation logic, usually converting
 * a source string (e.g., 'title') into a slug (e.g., 'my-title').
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoSlug
{
    /**
     * @param string $from   The name of the source property to generate the slug from.
     * @param bool   $unique Whether to ensure the generated slug is unique in the database.
     */
    public function __construct(
        public string $from,
        public bool $unique = true
    ) {
    }
}