<?php

namespace Ludelix\Database\Core;

/**
 * Trait for hiding sensitive fields during JSON serialization or array conversion.
 *
 * Use this trait in your entities to automatically exclude properties
 * defined in the $hidden array (e.g., passwords, tokens) when the object
 * is serialized to JSON or converted to an array.
 */
trait HiddenFieldsTrait
{
    /**
     * List of fields to be hidden during serialization.
     * @var array
     */
    protected array $hidden = [];

    /**
     * Returns the list of hidden fields.
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * Convert the entity instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $vars = get_object_vars($this);

        foreach ($this->getHidden() as $field) {
            unset($vars[$field]);
        }

        return $vars;
    }

    /**
     * Serializes the entity to JSON, excluding the fields defined in $hidden.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}