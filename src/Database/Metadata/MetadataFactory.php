<?php

namespace Ludelix\Database\Metadata;

use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;
use ReflectionClass;

/**
 * Factory for creating and caching EntityMetadata instances.
 *
 * Uses PHP Reflection to inspect entity classes and their attributes,
 * building an EntityMetadata object that serves as a cached schema definition.
 */
class MetadataFactory
{
    /** @var array<string, EntityMetadata> Internal cache of metadata instances. */
    protected array $metadataCache = [];

    /**
     * Retrieves (or creates) the metadata for a given entity class.
     *
     * @param string $className The fully qualified class name.
     * @return EntityMetadata The metadata instance.
     */
    public function getMetadata(string $className): EntityMetadata
    {
        if (!isset($this->metadataCache[$className])) {
            $this->metadataCache[$className] = $this->createMetadata($className);
        }

        return $this->metadataCache[$className];
    }

    /**
     * Creates a new metadata instance by reflecting the class and reading attributes.
     *
     * @param string $className
     * @return EntityMetadata
     */
    protected function createMetadata(string $className): EntityMetadata
    {
        $reflection = new ReflectionClass($className);
        $metadata = new EntityMetadata($className);

        // Process class attributes
        $entityAttributes = $reflection->getAttributes(Entity::class);
        if (!empty($entityAttributes)) {
            $entity = $entityAttributes[0]->newInstance();
            $metadata->setTableName($entity->table);
        }

        // Process property attributes
        foreach ($reflection->getProperties() as $property) {
            $columnAttributes = $property->getAttributes(Column::class);
            $primaryKeyAttributes = $property->getAttributes(PrimaryKey::class);

            if (!empty($columnAttributes)) {
                $column = $columnAttributes[0]->newInstance();
                $config = [
                    'column' => $column->name ?: $property->getName(),
                    'type' => $column->type,
                    'nullable' => $column->nullable,
                    'primary' => !empty($primaryKeyAttributes),
                    'cast' => $column->cast
                ];

                $metadata->addProperty($property->getName(), $config);
            }
        }

        return $metadata;
    }
}