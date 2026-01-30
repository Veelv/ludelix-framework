<?php

namespace Ludelix\Database\Repositories;

/**
 * Standard interface for all repositories.
 */
interface RepositoryInterface
{
    /**
     * Finds an entity by its ID.
     * @param mixed $id
     * @return object|null
     */
    public function find(mixed $id): ?object;

    /**
     * Finds all entities.
     * @return array
     */
    public function findAll(): array;

    /**
     * Finds entities by criteria.
     * @param array $criteria
     * @return array
     */
    public function findBy(array $criteria): array;

    /**
     * Saves an entity (persist + flush).
     * @param object $entity
     */
    public function save(object $entity): void;

    /**
     * Deletes an entity (remove + flush).
     * @param object $entity
     */
    public function delete(object $entity): void;
}