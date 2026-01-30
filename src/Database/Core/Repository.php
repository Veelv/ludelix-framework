<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\EntityMetadata;

/**
 * Base implementation of the generic Repository.
 * (Note: Often superseded by BaseRepository in Repositories namespace, check usage).
 */
class Repository
{
    protected EntityManager $entityManager;
    protected EntityMetadata $metadata;

    public function __construct(EntityManager $entityManager, EntityMetadata $metadata)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
    }

    /**
     * Finds an entity by ID.
     *
     * @param mixed $id
     * @return object|null
     */
    public function find(mixed $id): ?object
    {
        return $this->entityManager->find($this->metadata->getClassName(), $id);
    }

    /**
     * Finds all entities.
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->entityManager->findAll($this->metadata->getClassName());
    }

    /**
     * Finds entities by criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array
    {
        return $this->entityManager->findBy($this->metadata->getClassName(), $criteria);
    }

    /**
     * Saves an entity (persist + flush).
     *
     * @param object $entity
     */
    public function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Deletes an entity (remove + flush).
     *
     * @param object $entity
     */
    public function delete(object $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}