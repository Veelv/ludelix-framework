<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\EntityMetadata;

class Repository
{
    protected EntityManager $entityManager;
    protected EntityMetadata $metadata;
    
    public function __construct(EntityManager $entityManager, EntityMetadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
    }
    
    public function find(mixed $id): ?object
    {
        return $this->entityManager->find($this->metadata->getClassName(), $id);
    }
    
    public function findAll(): array
    {
        return $this->entityManager->findAll($this->metadata->getClassName());
    }
    
    public function findBy(array $criteria): array
    {
        return $this->entityManager->findBy($this->metadata->getClassName(), $criteria);
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
}