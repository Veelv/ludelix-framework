<?php

namespace Ludelix\Database\Metadata;

/**
 * Stores metadata about an entity class.
 *
 * Encapsulates all mapped information such as the table name, column mapping,
 * primary key, and type casting attributes for a specific entity class.
 */
class EntityMetadata
{
    /** @var string The fully qualified class name of the entity. */
    protected string $className;

    /** @var string The database table associated with the entity. */
    protected string $tableName;

    /** @var array<string, array> List of property configurations keyed by property name. */
    protected array $properties = [];

    /** @var string|null The name of the property acting as the primary key. */
    protected ?string $primaryKey = null;

    /** @var array<string, string> Mapping of database column names to property names. */
    protected array $columnMapping = [];

    /** @var array<string, string> Type casting definitions for properties. */
    protected array $casts = [];

    /**
     * Initializes the EntityMetadata.
     *
     * @param string $className The fully qualified class name.
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Gets the entity class name.
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Sets the database table name.
     *
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Gets the database table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Adds a mapped property to the metadata.
     *
     * @param string $name   The property name.
     * @param array  $config Configuration array (column name, type, etc.).
     */
    public function addProperty(string $name, array $config): void
    {
        $this->properties[$name] = $config;
        $columnName = $config['column'] ?? $name;
        $this->columnMapping[$columnName] = $name;

        if ($config['primary'] ?? false) {
            $this->primaryKey = $name;
        }

        if (isset($config['cast'])) {
            $this->casts[$name] = $config['cast'];
        }
    }

    /**
     * Gets the cast type for a specific property.
     *
     * @param string $property Property name.
     * @return string|null Cast type (e.g., 'json', 'int', 'datetime') or null.
     */
    public function getCast(string $property): ?string
    {
        return $this->casts[$property] ?? null;
    }

    /**
     * Gets all registered property mappings.
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Gets the name of the primary key property.
     *
     * @return string|null
     */
    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    /**
     * Resolves a property name from a database column name.
     *
     * @param string $column Database column name.
     * @return string|null Property name or null if not mapped.
     */
    public function getPropertyByColumn(string $column): ?string
    {
        return $this->columnMapping[$column] ?? null;
    }

    /**
     * Resolves a database column name from a property name.
     *
     * @param string $property Property name.
     * @return string|null Column name or null if not mapped.
     */
    public function getColumnByProperty(string $property): ?string
    {
        if (isset($this->properties[$property])) {
            return $this->properties[$property]['column'] ?? $property;
        }
        return null;
    }
}