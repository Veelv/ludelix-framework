<?php

namespace Ludelix\Database\Core;

use SplObjectStorage;

class UnitOfWork
{
    protected SplObjectStorage $newEntities;
    protected SplObjectStorage $managedEntities;
    protected SplObjectStorage $removedEntities;
    
    public function __construct()
    {
        $this->newEntities = new SplObjectStorage();
        $this->managedEntities = new SplObjectStorage();
        $this->removedEntities = new SplObjectStorage();
    }
    
    public function registerNew(object $entity): void
    {
        $this->newEntities->attach($entity);
    }
    
    public function registerManaged(object $entity): void
    {
        $this->managedEntities->attach($entity);
    }
    
    public function registerRemoved(object $entity): void
    {
        $this->removedEntities->attach($entity);
    }
    
    public function commit(): void
    {
        // Process insertions
        foreach ($this->newEntities as $entity) {
            $this->insertEntity($entity);
        }
        
        // Process deletions
        foreach ($this->removedEntities as $entity) {
            $this->deleteEntity($entity);
        }
        
        $this->clear();
    }
    
    protected function insertEntity(object $entity): void
    {
        $this->processEntityData($entity);
        // Implementation for inserting entity
    }
    
    protected function processEntityData(object $entity): void
    {
        $reflection = new \ReflectionClass($entity);
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if ($property->isInitialized($entity)) {
                $value = $property->getValue($entity);
                if (is_string($value)) {
                    $property->setValue($entity, trim($value));
                }
            }
        }
    }
    
    protected function deleteEntity(object $entity): void
    {
        // Implementation for deleting entity
    }
    
    public function clear(): void
    {
        $this->newEntities = new SplObjectStorage();
        $this->managedEntities = new SplObjectStorage();
        $this->removedEntities = new SplObjectStorage();
    }
}