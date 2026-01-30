<?php

namespace Ludelix\Database\Attributes;

use Attribute;

/**
 * Marks a class as a database entity.
 *
 * This attribute associates a PHP class with a database table and defines optional
 * metadata such as the database schema, read-only status, and related simple constraints.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    /**
     * @param string      $table             The name of the database table.
     * @param string|null $database          The database name (optional).
     * @param string|null $schema            The schema name (e.g., for PostgreSQL).
     * @param array       $indexes           List of indexes to key on the table.
     * @param array       $uniqueConstraints List of unique constraints.
     * @param bool        $readOnly          If true, the entity cannot be modified after creation.
     * @param string|null $repository        Custom repository class FQN.
     */
    public function __construct(
        public string $table,
        public ?string $database = null,
        public ?string $schema = null,
        public array $indexes = [],
        public array $uniqueConstraints = [],
        public bool $readOnly = false,
        public ?string $repository = null
    ) {
    }
}