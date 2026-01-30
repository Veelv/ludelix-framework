<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\EntityMetadata;
use PDO;

/**
 * Builds and executes database queries.
 *
 * This class provides a fluent interface for constructing SQL queries (SELECT, INSERT, UPDATE, DELETE).
 * It handles parameter binding, SQL generation, and result hydration into entities.
 */
class QueryBuilder
{
    protected PDO $connection;
    protected EntityMetadata $metadata;
    protected ?string $alias;
    protected array $where = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected array $parameters = [];

    /**
     * @param PDO            $connection Database connection instance.
     * @param EntityMetadata $metadata   Metadata of the entity being queried.
     * @param string|null    $alias      Table alias for using in queries (default: 'e').
     */
    public function __construct(PDO $connection, EntityMetadata $metadata, ?string $alias = 'e')
    {
        $this->connection = $connection;
        $this->metadata = $metadata;
        $this->alias = $alias;
    }

    /**
     * Adds a WHERE clause to the query.
     *
     * @param string $field    Field name.
     * @param string $operator Operator (e.g., '=', '>', 'LIKE').
     * @param mixed  $value    Value to compare.
     * @return self
     */
    public function where(string $field, string $operator, mixed $value): self
    {
        $paramName = 'param_' . count($this->parameters);
        $prefix = $this->alias ? "{$this->alias}." : '';
        $this->where[] = "{$prefix}{$field} {$operator} :{$paramName}";
        $this->parameters[$paramName] = $value;
        return $this;
    }

    /**
     * Adds an ORDER BY clause.
     *
     * @param string $field     Field to sort by.
     * @param string $direction Direction ('ASC' or 'DESC').
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $prefix = $this->alias ? "{$this->alias}." : '';
        $this->orderBy[] = "{$prefix}{$field} {$direction}";
        return $this;
    }

    /**
     * Sets a LIMIT on the query results.
     *
     * @param int $limit Maximum number of results.
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Executes the query and returns the first result.
     *
     * @return object|null The first entity found or null.
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Executes an INSERT query.
     *
     * @param array $values Associative array of column => value (already mapped from entity property).
     * @return bool True on success.
     */
    public function insert(array $values): bool
    {
        $columns = implode(', ', array_keys($values));
        $placeholders = ':' . implode(', :', array_keys($values));

        $sql = "INSERT INTO {$this->metadata->getTableName()} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->connection->prepare($sql);

        foreach ($values as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        return $stmt->execute();
    }

    /**
     * Executes an UPDATE query.
     *
     * @param array $values Associative array of column => value to update.
     * @return bool True on success.
     */
    public function update(array $values): bool
    {
        $setClauses = [];
        foreach (array_keys($values) as $column) {
            $setClauses[] = "{$column} = :set_{$column}";
        }

        $sql = "UPDATE {$this->metadata->getTableName()} SET " . implode(', ', $setClauses);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $stmt = $this->connection->prepare($sql);

        // Bind update values
        foreach ($values as $param => $value) {
            $stmt->bindValue(":set_{$param}", $value);
        }

        // Bind where parameters
        foreach ($this->parameters as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        return $stmt->execute();
    }

    /**
     * Executes a DELETE query.
     *
     * @return bool True on success.
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->metadata->getTableName()}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        $stmt = $this->connection->prepare($sql);

        foreach ($this->parameters as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        return $stmt->execute();
    }

    /**
     * Executes a SELECT query and returns hydrated entities.
     *
     * @return array List of entity objects.
     */
    public function get(): array
    {
        $sql = $this->buildQuery();
        $stmt = $this->connection->prepare($sql);

        foreach ($this->parameters as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'hydrate'], $results);
    }

    protected function buildQuery(): string
    {
        $sql = "SELECT {$this->alias}.* FROM {$this->metadata->getTableName()} {$this->alias}";

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        return $sql;
    }

    protected function hydrate(array $row): object
    {
        $entityClass = $this->metadata->getClassName();
        $entity = new $entityClass();

        foreach ($row as $column => $value) {
            $property = $this->metadata->getPropertyByColumn($column);
            if ($property && property_exists($entity, $property)) {
                $cast = $this->metadata->getCast($property);
                $value = $this->castValue($value, $cast);

                $entity->$property = $value;
            }
        }

        return $entity;
    }

    protected function castValue(mixed $value, ?string $cast): mixed
    {
        if ($value === null)
            return null;
        if (!$cast)
            return is_string($value) ? trim($value) : $value;

        return match ($cast) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'json', 'array' => json_decode($value, true) ?: [],
            'object' => json_decode($value) ?: new \stdClass(),
            'datetime' => new \DateTime($value),
            default => $value,
        };
    }
}