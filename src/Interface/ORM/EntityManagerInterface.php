<?php

namespace Ludelix\Interface\ORM;

use Ludelix\ORM\Query\QueryBuilder;
use Ludelix\ORM\Repository\Repository;

/**
 * EntityManagerInterface - Entity Manager Contract
 * 
 * Defines the contract for entity management
 * 
 * @package Ludelix\Interface\ORM
 */
interface EntityManagerInterface
{
    /**
     * Find entity by primary key
     */
    public function find(string $entityClass, mixed $id, array $options = []): ?object;
    
    /**
     * Find all entities
     */
    public function findAll(string $entityClass, array $options = []): array;
    
    /**
     * Find entities by criteria
     */
    public function findBy(string $entityClass, array $criteria, array $options = []): array;
    
    /**
     * Find one entity by criteria
     */
    public function findOneBy(string $entityClass, array $criteria): ?object;
    
    /**
     * Persist entity
     */
    public function persist(object $entity): void;
    
    /**
     * Remove entity
     */
    public function remove(object $entity): void;
    
    /**
     * Flush changes to database
     */
    public function flush(): void;
    
    /**
     * Create query builder
     */
    public function createQueryBuilder(string $entityClass, string $alias = 'e'): QueryBuilder;
    
    /**
     * Get repository for entity
     */
    public function getRepository(string $entityClass): Repository;
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): void;
    
    /**
     * Commit transaction
     */
    public function commit(): void;
    
    /**
     * Rollback transaction
     */
    public function rollback(): void;
    
    /**
     * Execute in transaction
     */
    public function transaction(callable $callback): mixed;
    
    /**
     * Clear entity manager
     */
    public function clear(): void;
    
    /**
     * Check if entity is managed
     */
    public function contains(object $entity): bool;
    
    /**
     * Detach entity from management
     */
    public function detach(object $entity): void;
    
    /**
     * Merge entity state
     */
    public function merge(object $entity): object;
    
    /**
     * Refresh entity from database
     */
    public function refresh(object $entity): void;
}