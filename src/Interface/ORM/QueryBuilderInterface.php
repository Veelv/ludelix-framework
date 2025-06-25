<?php

namespace Ludelix\Interface\ORM;

/**
 * QueryBuilderInterface - Query Builder Contract
 * 
 * Defines the contract for query builders
 * 
 * @package Ludelix\Interface\ORM
 */
interface QueryBuilderInterface
{
    /**
     * Add SELECT clause
     */
    public function select(string ...$fields): self;
    
    /**
     * Add WHERE clause
     */
    public function where(string $field, string $operator, mixed $value): self;
    
    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $field, array $values): self;
    
    /**
     * Add OR WHERE clause
     */
    public function orWhere(string $field, string $operator, mixed $value): self;
    
    /**
     * Add JOIN clause
     */
    public function join(string $table, string $condition, string $type = 'INNER'): self;
    
    /**
     * Add LEFT JOIN clause
     */
    public function leftJoin(string $table, string $condition): self;
    
    /**
     * Add RIGHT JOIN clause
     */
    public function rightJoin(string $table, string $condition): self;
    
    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $field, string $direction = 'ASC'): self;
    
    /**
     * Add GROUP BY clause
     */
    public function groupBy(string ...$fields): self;
    
    /**
     * Add HAVING clause
     */
    public function having(string $condition): self;
    
    /**
     * Set LIMIT
     */
    public function limit(int $limit): self;
    
    /**
     * Set OFFSET
     */
    public function offset(int $offset): self;
    
    /**
     * Get first result
     */
    public function first(): ?object;
    
    /**
     * Get all results
     */
    public function get(): array;
    
    /**
     * Count results
     */
    public function count(): int;
    
    /**
     * Check if results exist
     */
    public function exists(): bool;
    
    /**
     * Get raw SQL query
     */
    public function toSql(): string;
}