<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Defines a database column mapping for an entity property.
 *
 * This attribute is used to map a PHP property to a specific database table column,
 * allowing customization of type, length, nullability, and other constraints.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param string|null $name     The name of the database column. If null, the property name is used.
     * @param string      $type     The database column type (e.g., 'string', 'integer', 'text').
     * @param int|null    $length   The maximum length of the column (for strings).
     * @param bool        $nullable Whether the column can accept NULL values.
     * @param mixed       $default  The default value for the column.
     * @param bool        $unique   Whether the column value must be unique across the table.
     * @param string|null $comment  A comment describing the column's purpose.
     * @param string|null $cast     The type casting strategy (e.g., 'json', 'bool', 'datetime').
     * @param array       $options  Additional driver-specific options.
     */
    public function __construct(
        public ?string $name = null,
        public string $type = 'string',
        public ?int $length = null,
        public bool $nullable = false,
        public mixed $default = null,
        public bool $unique = false,
        public ?string $comment = null,
        public ?string $cast = null,
        public array $options = []
    ) {
    }
}