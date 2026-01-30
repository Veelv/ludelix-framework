<?php

namespace Ludelix\Database\Metadata;

/**
 * Value object representing the metadata of a single entity property.
 *
 * Stores details about type, nullability, defaults, and relationships
 * for a specific property within an entity.
 */
class PropertyMetadata
{
    /** @var string The name of the property. */
    protected string $name;

    /** @var string The data type (e.g., 'string', 'int'). */
    protected string $type;

    /** @var bool Whether the property can be null. */
    protected bool $nullable;

    /** @var mixed The default value. */
    protected mixed $default;

    /**
     * Initializes the PropertyMetadata.
     *
     * @param string $name     Property name.
     * @param string $type     Data type.
     * @param bool   $nullable Is nullable?
     * @param mixed  $default  Default value.
     */
    public function __construct(string $name, string $type, bool $nullable = false, mixed $default = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->default = $default;
    }

    /**
     * Gets the property name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the property data type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Checks if the property is nullable.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Gets the default value.
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }
}