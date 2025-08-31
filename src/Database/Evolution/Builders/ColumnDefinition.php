<?php

namespace Ludelix\Database\Evolution\Builders;

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

    public function __construct(string $name, string $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function primary(): self
    {
        $this->primary = true;
        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }

    public function check(string $condition): self
    {
        $this->checks[] = $condition;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->options['on_update'] = $action;
        return $this;
    }

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

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }
}