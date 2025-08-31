<?php

namespace Ludelix\Interface\ORM;

use Ludelix\ORM\Query\QueryBuilder;
use Ludelix\ORM\Metadata\EntityMetadata;

/**
 * RepositoryInterface - Repository Contract
 * 
 * Defines the contract for entity repositories
 * 
 * @package Ludelix\Interface\ORM
 */
interface RepositoryInterface
{
    /**
     * Find entity by ID
     */
    public function find(mixed $id): ?object;
    
    /**
     * Find all entities
     */
    public function findAll(array $options = []): array;
    
    /**
     * Find entities by criteria
     */
    public function findBy(array $criteria, array $options = []): array;
    
    /**
     * Find one entity by criteria
     */
    public function findOneBy(array $criteria): ?object;
    
    /**
     * Create query builder
     */
    public function createQueryBuilder(string $alias = 'e'): QueryBuilder;
    
    /**
     * Count entities
     */
    public function count(array $criteria = []): int;
    
    /**
     * Save entity
     */
    public function save(object $entity): void;
    
    /**
     * Delete entity
     */
    public function delete(object $entity): void;
    
    /**
     * Delete by criteria
     */
    public function deleteBy(array $criteria): int;
    
    /**
     * Get entity class name
     */
    public function getClassName(): string;
    
    /**
     * Get entity metadata
     */
    public function getMetadata(): EntityMetadata;
}