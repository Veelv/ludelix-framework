<?php

namespace Ludelix\Database\Evolution\Builders;

/**
 * Defines the schema and configuration of a database column.
 *
 * This class provides a fluent interface to configure column properties
 * such as type, nullability, default values, and constraints.
 */
class ColumnDefinition
{
    protected string $name;
    protected string $type;
    protected array $options;
    protected bool $nullable = false;
    protected mixed $default = null;
    protected bool $autoIncrement = false;
    protected bool $primary = false;
    protected bool $unique = false;
    protected array $checks = [];

    /**
     * Creates a new column definition.
     *
     * @param string $name    The column name.
     * @param string $type    The data type (e.g., VARCHAR, INT).
     * @param array  $options Additional options (length, precision, etc.).
     */
    public function __construct(string $name, string $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Sets whether the column allows NULL values.
     *
     * @param bool $nullable True to allow NULL, false otherwise.
     * @return self
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * Sets the default value for the column.
     *
     * @param mixed $value The default value.
     * @return self
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Marks the column as auto-incrementing.
     *
     * @return self
     */
    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    /**
     * Marks the column as a primary key.
     *
     * @return self
     */
    public function primary(): self
    {
        $this->primary = true;
        return $this;
    }

    /**
     * Adds a unique constraint to the column.
     *
     * @return self
     */
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    /**
     * Adds a CHECK constraint to the column.
     *
     * @param string $condition The SQL condition for the check.
     * @return self
     */
    public function check(string $condition): self
    {
        $this->checks[] = $condition;
        return $this;
    }

    /**
     * Sets the action to perform on update (e.g., CURRENT_TIMESTAMP).
     *
     * @param string $action The action to perform (SQL).
     * @return self
     */
    public function onUpdate(string $action): self
    {
        $this->options['on_update'] = $action;
        return $this;
    }

    /**
     * Generates the SQL definition for this column.
     *
     * @return string The SQL string.
     */
    public function toSql(): string
    {
        $sql = "{$this->name} {$this->type}";

        // Add length/precision
        if (isset($this->options['length'])) {
            $sql .= "({$this->options['length']})";
        } elseif (isset($this->options['precision']) && isset($this->options['scale'])) {
            $sql .= "({$this->options['precision']}, {$this->options['scale']})";
        }

        // Nullable
        $sql .= $this->nullable ? ' NULL' : ' NOT NULL';

        // Default value
        if ($this->default !== null) {
            if (is_string($this->default) && !in_array(strtoupper($this->default), ['CURRENT_TIMESTAMP', 'NULL'])) {
                $sql .= " DEFAULT '{$this->default}'";
            } else {
                $sql .= " DEFAULT {$this->default}";
            }
        }

        // Auto increment
        if ($this->autoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }

        // Primary key
        if ($this->primary) {
            $sql .= ' PRIMARY KEY';
        }

        // Unique
        if ($this->unique) {
            $sql .= ' UNIQUE';
        }

        // On update
        if (isset($this->options['on_update'])) {
            $sql .= " ON UPDATE {$this->options['on_update']}";
        }

        return $sql;
    }

    /**
     * Gets the column name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the column data type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the list of check constraints.
     *
     * @return array
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}