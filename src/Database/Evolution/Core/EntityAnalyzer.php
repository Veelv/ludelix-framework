<?php

namespace Ludelix\Database\Evolution\Core;

use ReflectionClass;
use ReflectionAttribute;

class EntityAnalyzer
{
    public function generateEvolution(string $entityClass): array
    {
        if (!class_exists($entityClass)) {
            throw new \Exception("Entity class '{$entityClass}' not found");
        }

        $reflection = new ReflectionClass($entityClass);
        $entityAttribute = $this->getEntityAttribute($reflection);
        
        if (!$entityAttribute) {
            throw new \Exception("Class '{$entityClass}' is not marked as Entity");
        }

        $tableName = $entityAttribute->table ?? $this->getTableNameFromClass($entityClass);
        $columns = $this->analyzeColumns($reflection);
        $indexes = $this->analyzeIndexes($reflection);
        $relations = $this->analyzeRelations($reflection);

        return [
            'table' => $tableName,
            'columns' => $columns,
            'indexes' => $indexes,
            'relations' => $relations,
            'evolution_type' => 'create_table'
        ];
    }

    public function compareWithDatabase(string $entityClass, array $currentSchema): array
    {
        $entitySchema = $this->generateEvolution($entityClass);
        $tableName = $entitySchema['table'];
        
        if (!isset($currentSchema[$tableName])) {
            return [
                'action' => 'create_table',
                'table' => $tableName,
                'definition' => $entitySchema
            ];
        }

        $differences = $this->compareSchemas($entitySchema, $currentSchema[$tableName]);
        
        if (empty($differences)) {
            return ['action' => 'no_changes'];
        }

        return [
            'action' => 'modify_table',
            'table' => $tableName,
            'changes' => $differences
        ];
    }

    protected function getEntityAttribute(ReflectionClass $reflection): ?object
    {
        $attributes = $reflection->getAttributes();
        
        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'Entity')) {
                return $attribute->newInstance();
            }
        }
        
        return null;
    }

    protected function analyzeColumns(ReflectionClass $reflection): array
    {
        $columns = [];
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $columnAttribute = $this->getColumnAttribute($property);
            
            if ($columnAttribute) {
                $columns[$property->getName()] = [
                    'type' => $columnAttribute->type ?? 'varchar',
                    'length' => $columnAttribute->length ?? null,
                    'nullable' => $columnAttribute->nullable ?? true,
                    'default' => $columnAttribute->default ?? null,
                    'primary' => $this->isPrimaryKey($property),
                    'auto_increment' => $this->isAutoIncrement($property)
                ];
            }
        }
        
        return $columns;
    }

    protected function analyzeIndexes(ReflectionClass $reflection): array
    {
        $indexes = [];
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes();
            
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                
                if (str_ends_with($attributeName, 'Index')) {
                    $instance = $attribute->newInstance();
                    $indexes[] = [
                        'name' => $instance->name ?? $property->getName() . '_idx',
                        'columns' => [$property->getName()],
                        'unique' => str_contains($attributeName, 'Unique')
                    ];
                }
            }
        }
        
        return $indexes;
    }

    protected function analyzeRelations(ReflectionClass $reflection): array
    {
        $relations = [];
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property) {
            $attributes = $property->getAttributes();
            
            foreach ($attributes as $attribute) {
                $attributeName = $attribute->getName();
                
                if (str_contains($attributeName, 'ToMany') || str_contains($attributeName, 'ToOne')) {
                    $instance = $attribute->newInstance();
                    $relations[] = [
                        'property' => $property->getName(),
                        'type' => $this->getRelationType($attributeName),
                        'target' => $instance->targetEntity ?? null,
                        'foreign_key' => $instance->foreignKey ?? null
                    ];
                }
            }
        }
        
        return $relations;
    }

    protected function getColumnAttribute($property): ?object
    {
        $attributes = $property->getAttributes();
        
        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'Column')) {
                return $attribute->newInstance();
            }
        }
        
        return null;
    }

    protected function isPrimaryKey($property): bool
    {
        $attributes = $property->getAttributes();
        
        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'PrimaryKey')) {
                return true;
            }
        }
        
        return false;
    }

    protected function isAutoIncrement($property): bool
    {
        $attributes = $property->getAttributes();
        
        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'AutoIncrement')) {
                return true;
            }
        }
        
        return false;
    }

    protected function getTableNameFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);
        
        // Remove 'Entity' suffix if present
        if (str_ends_with($shortName, 'Entity')) {
            $shortName = substr($shortName, 0, -6);
        }
        
        // Convert to snake_case and pluralize
        $tableName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
        return $tableName . 's';
    }

    protected function getRelationType(string $attributeName): string
    {
        if (str_contains($attributeName, 'OneToMany')) return 'one_to_many';
        if (str_contains($attributeName, 'ManyToOne')) return 'many_to_one';
        if (str_contains($attributeName, 'ManyToMany')) return 'many_to_many';
        if (str_contains($attributeName, 'OneToOne')) return 'one_to_one';
        
        return 'unknown';
    }

    protected function compareSchemas(array $entitySchema, array $dbSchema): array
    {
        $differences = [];
        
        // Compare columns
        $entityColumns = $entitySchema['columns'];
        $dbColumns = $this->indexColumnsByName($dbSchema['columns']);
        
        foreach ($entityColumns as $name => $definition) {
            if (!isset($dbColumns[$name])) {
                $differences['add_columns'][$name] = $definition;
            } elseif ($this->columnsAreDifferent($definition, $dbColumns[$name])) {
                $differences['modify_columns'][$name] = $definition;
            }
        }
        
        foreach ($dbColumns as $name => $column) {
            if (!isset($entityColumns[$name])) {
                $differences['drop_columns'][] = $name;
            }
        }
        
        return $differences;
    }

    protected function indexColumnsByName(array $columns): array
    {
        $indexed = [];
        foreach ($columns as $column) {
            $indexed[$column['Field']] = $column;
        }
        return $indexed;
    }

    protected function columnsAreDifferent(array $entityColumn, array $dbColumn): bool
    {
        // Simplified comparison - would need more sophisticated logic
        return $entityColumn['type'] !== $this->normalizeDbType($dbColumn['Type']);
    }

    protected function normalizeDbType(string $dbType): string
    {
        // Convert database types to entity types
        if (str_contains($dbType, 'varchar')) return 'varchar';
        if (str_contains($dbType, 'int')) return 'int';
        if (str_contains($dbType, 'text')) return 'text';
        if (str_contains($dbType, 'datetime')) return 'datetime';
        if (str_contains($dbType, 'timestamp')) return 'timestamp';
        
        return $dbType;
    }
}