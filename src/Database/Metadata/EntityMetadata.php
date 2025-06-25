<?php

namespace Ludelix\Database\Metadata;

class EntityMetadata
{
    protected string $className;
    protected string $tableName;
    protected array $properties = [];
    protected ?string $primaryKey = null;
    protected array $columnMapping = [];
    
    public function __construct(string $className)
    {
        $this->className = $className;
    }
    
    public function getClassName(): string
    {
        return $this->className;
    }
    
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }
    
    public function getTableName(): string
    {
        return $this->tableName;
    }
    
    public function addProperty(string $name, array $config): void
    {
        $this->properties[$name] = $config;
        $columnName = $config['column'] ?? $name;
        $this->columnMapping[$columnName] = $name;
        
        if ($config['primary'] ?? false) {
            $this->primaryKey = $name;
        }
    }
    
    public function getProperties(): array
    {
        return $this->properties;
    }
    
    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }
    
    public function getPropertyByColumn(string $column): ?string
    {
        return $this->columnMapping[$column] ?? null;
    }
}