<?php

namespace Ludelix\Database\Core;

use Ludelix\Database\Metadata\EntityMetadata;
use PDO;

class QueryBuilder
{
    protected PDO $connection;
    protected EntityMetadata $metadata;
    protected string $alias;
    protected array $where = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected array $parameters = [];
    
    public function __construct(PDO $connection, EntityMetadata $metadata, string $alias = 'e')
    {
        $this->connection = $connection;
        $this->metadata = $metadata;
        $this->alias = $alias;
    }
    
    public function where(string $field, string $operator, mixed $value): self
    {
        $paramName = 'param_' . count($this->parameters);
        $this->where[] = "{$this->alias}.{$field} {$operator} :{$paramName}";
        $this->parameters[$paramName] = $value;
        return $this;
    }
    
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$this->alias}.{$field} {$direction}";
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
    
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
                $entity->$property = is_string($value) ? trim($value) : $value;
            }
        }
        
        return $entity;
    }
}