<?php

namespace Ludelix\Database\Metadata;

class PropertyMetadata
{
    protected string $name;
    protected string $type;
    protected bool $nullable;
    protected mixed $default;
    protected bool $primary;
    protected string $column;
    
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->type = $config['type'] ?? 'string';
        $this->nullable = $config['nullable'] ?? false;
        $this->default = $config['default'] ?? null;
        $this->primary = $config['primary'] ?? false;
        $this->column = $config['column'] ?? $name;
    }
    
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function isNullable(): bool { return $this->nullable; }
    public function getDefault(): mixed { return $this->default; }
    public function isPrimary(): bool { return $this->primary; }
    public function getColumn(): string { return $this->column; }
}