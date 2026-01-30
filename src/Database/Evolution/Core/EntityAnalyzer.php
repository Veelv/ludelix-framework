<?php

namespace Ludelix\Database\Evolution\Core;

use ReflectionClass;
use ReflectionAttribute;
use ReflectionProperty;
use Exception;

/**
 * Analyzes entity classes to determine their database schema structure.
 *
 * This class uses PHP Reflection to inspect classes marked with the `#[Entity]` attribute,
 * extracting table names, columns, indexes, and relationships to generate a schema definition.
 */
class EntityAnalyzer
{
    /**
     * Generates a complete evolution schema definition based on the entity class.
     *
     * @param string $entityClass The fully qualified class name of the entity.
     * @return array The schema definition array containing table, columns, indexes, and relations.
     * @throws Exception If the class not found or is not marked as an entity.
     */
    public function generateEvolution(string $entityClass): array
    {
        if (!class_exists($entityClass)) {
            throw new Exception("Entity class '{$entityClass}' not found");
        }

        $reflection = new ReflectionClass($entityClass);
        $entityAttribute = $this->getEntityAttribute($reflection);

        if (!$entityAttribute) {
            throw new Exception("Class '{$entityClass}' is not marked as Entity");
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

    /**
     * Compares the generated entity schema with the current database schema.
     *
     * Determines if a table needs to be created, modified, or if no changes are required.
     *
     * @param string $entityClass   The entity class name.
     * @param array  $currentSchema The current database schema structure.
     * @return array The comparison result indicating the necessary action (create_table, modify_table, no_changes).
     * @throws Exception
     */
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

    /**
     * Retrieves the `#[Entity]` attribute instance from the class.
     *
     * @param ReflectionClass $reflection The reflection instance of the class.
     * @return object|null The attribute instance or null if not found.
     */
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

    /**
     * Analyzes class properties to build column definitions.
     *
     * @param ReflectionClass $reflection
     * @return array Map of column definitions keyed by property name.
     */
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

    /**
     * Analyzes class properties to find index definitions.
     *
     * @param ReflectionClass $reflection
     * @return array List of index definitions.
     */
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

    /**
     * Analyzes class properties to identify entity relationships.
     *
     * @param ReflectionClass $reflection
     * @return array List of relation definitions.
     */
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

    /**
     * Retrieves the `#[Column]` attribute from a property.
     *
     * @param ReflectionProperty $property
     * @return object|null
     */
    protected function getColumnAttribute(ReflectionProperty $property): ?object
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'Column')) {
                return $attribute->newInstance();
            }
        }

        return null;
    }

    /**
     * Checks if a property is marked as a Primary Key.
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    protected function isPrimaryKey(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'PrimaryKey')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a property is marked as Auto Increment.
     *
     * @param ReflectionProperty $property
     * @return bool
     */
    protected function isAutoIncrement(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            if (str_ends_with($attribute->getName(), 'AutoIncrement')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Derives the table name from the class name following conventions.
     *
     * @param string $className
     * @return string Snake case table name (e.g., UserEntity -> users).
     */
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

    /**
     * Determines the relationship type string from the attribute name.
     *
     * @param string $attributeName
     * @return string (one_to_many, many_to_one, etc.)
     */
    protected function getRelationType(string $attributeName): string
    {
        if (str_contains($attributeName, 'OneToMany'))
            return 'one_to_many';
        if (str_contains($attributeName, 'ManyToOne'))
            return 'many_to_one';
        if (str_contains($attributeName, 'ManyToMany'))
            return 'many_to_many';
        if (str_contains($attributeName, 'OneToOne'))
            return 'one_to_one';

        return 'unknown';
    }

    /**
     * Computes the differences between entity definition and database schema.
     *
     * @param array $entitySchema
     * @param array $dbSchema
     * @return array Array of differences (add_columns, drop_columns, modify_columns).
     */
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

    /**
     * Helper to index column array by 'Field' name.
     *
     * @param array $columns
     * @return array
     */
    protected function indexColumnsByName(array $columns): array
    {
        $indexed = [];
        foreach ($columns as $column) {
            $indexed[$column['Field']] = $column;
        }
        return $indexed;
    }

    /**
     * Checks if a column definition differs from the database column.
     *
     * @param array $entityColumn
     * @param array $dbColumn
     * @return bool
     */
    protected function columnsAreDifferent(array $entityColumn, array $dbColumn): bool
    {
        // Simplified comparison - real-world implementation would be more robust
        return $entityColumn['type'] !== $this->normalizeDbType($dbColumn['Type']);
    }

    /**
     * Normalizes database types to a standard format for comparison.
     *
     * @param string $dbType
     * @return string
     */
    protected function normalizeDbType(string $dbType): string
    {
        // Convert database types to entity types
        if (str_contains($dbType, 'varchar'))
            return 'varchar';
        if (str_contains($dbType, 'int'))
            return 'int';
        if (str_contains($dbType, 'text'))
            return 'text';
        if (str_contains($dbType, 'datetime'))
            return 'datetime';
        if (str_contains($dbType, 'timestamp'))
            return 'timestamp';

        return $dbType;
    }
}