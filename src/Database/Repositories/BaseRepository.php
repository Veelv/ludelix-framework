<?php

namespace Ludelix\Database\Repositories;

use Ludelix\Database\Core\EntityManager;
use Ludelix\Database\Metadata\EntityMetadata;

class BaseRepository
{
    protected EntityManager $entityManager;
    protected EntityMetadata $metadata;
    protected string $entityClass;
    
    public function __construct(EntityManager $entityManager, EntityMetadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->entityClass = $metadata->getClassName();
    }
    
    public function find(mixed $id): ?object
    {
        return $this->entityManager->find($this->entityClass, $id);
    }
    
    public function findAll(): array
    {
        return $this->entityManager->findAll($this->entityClass);
    }
    
    public function findBy(array $criteria): array
    {
        return $this->entityManager->findBy($this->entityClass, $criteria);
    }
    
    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }
    
    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
    
    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
    
    public function createQueryBuilder(string $alias = 'e')
    {
        return $this->entityManager->createQueryBuilder($this->entityClass, $alias);
    }
}