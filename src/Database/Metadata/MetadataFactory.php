<?php

namespace Ludelix\Database\Metadata;

use Ludelix\Database\Attributes\Entity;
use Ludelix\Database\Attributes\Column;
use Ludelix\Database\Attributes\PrimaryKey;
use ReflectionClass;

class MetadataFactory
{
    protected array $metadataCache = [];
    
    public function getMetadata(string $className): EntityMetadata
    {
        if (!isset($this->metadataCache[$className])) {
            $this->metadataCache[$className] = $this->createMetadata($className);
        }
        
        return $this->metadataCache[$className];
    }
    
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
                    'primary' => !empty($primaryKeyAttributes)
                ];
                
                $metadata->addProperty($property->getName(), $config);
            }
        }
        
        return $metadata;
    }
}