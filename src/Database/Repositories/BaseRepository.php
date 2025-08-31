<?php

namespace Ludelix\Database\Repositories;

use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Metadata\EntityMetadata;
use Ludelix\Translation\Support\TranslatableTrait;

class BaseRepository
{
    use TranslatableTrait;

    protected EntityManager $entityManager;
    protected EntityMetadata $metadata;
    protected string $entityClass;
    
    public function __construct(EntityManager $entityManager, EntityMetadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->entityClass = $metadata->getClassName();
        
        // Set default translation namespace based on entity class
        $this->setTranslationNamespace($this->getDefaultTranslationNamespace());
    }
    
    /**
     * Get default translation namespace based on entity class
     */
    protected function getDefaultTranslationNamespace(): string
    {
        $className = $this->entityClass;
        $parts = explode('\\', $className);
        $entityName = end($parts);
        
        // Convert CamelCase to snake_case for translation keys
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $entityName));
        
        return 'entities.' . $key;
    }
    
    /**
     * Find entity with translation support
     */
    public function find(mixed $id): ?object
    {
        $entity = $this->entityManager->find($this->entityClass, $id);
        
        if ($entity && method_exists($entity, 'setTranslator')) {
            $entity->setTranslator($this->translator);
            $entity->setLocale($this->currentLocale);
        }
        
        return $entity;
    }
    
    /**
     * Find all entities with translation support
     */
    public function findAll(): array
    {
        $entities = $this->entityManager->findAll($this->entityClass);
        
        foreach ($entities as $entity) {
            if (method_exists($entity, 'setTranslator')) {
                $entity->setTranslator($this->translator);
                $entity->setLocale($this->currentLocale);
            }
        }
        
        return $entities;
    }
    
    /**
     * Find entities by criteria with translation support
     */
    public function findBy(array $criteria): array
    {
        $entities = $this->entityManager->findBy($this->entityClass, $criteria);
        
        foreach ($entities as $entity) {
            if (method_exists($entity, 'setTranslator')) {
                $entity->setTranslator($this->translator);
                $entity->setLocale($this->currentLocale);
            }
        }
        
        return $entities;
    }
    
    /**
     * Find one entity by criteria with translation support
     */
    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    /**
     * Save entity with translation support
     */
    public function save(object $entity): void
    {
        // Set translator on entity before saving
        if (method_exists($entity, 'setTranslator')) {
            $entity->setTranslator($this->translator);
            $entity->setLocale($this->currentLocale);
        }
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
    
    /**
     * Delete entity
     */
    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
    
    /**
     * Create query builder
     */
    public function createQueryBuilder(string $alias = 'e')
    {
        return $this->entityManager->createQueryBuilder($this->entityClass, $alias);
    }
    
    /**
     * Get translated error message
     */
    public function getTranslatedError(string $key, array $parameters = []): string
    {
        return $this->trans('errors.' . $key, $parameters);
    }
    
    /**
     * Get translated success message
     */
    public function getTranslatedSuccess(string $key, array $parameters = []): string
    {
        return $this->trans('success.' . $key, $parameters);
    }
    
    /**
     * Get translated validation message
     */
    public function getTranslatedValidation(string $key, array $parameters = []): string
    {
        return $this->trans('validation.' . $key, $parameters);
    }
}