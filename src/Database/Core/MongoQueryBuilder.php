<?php

namespace Ludelix\Database\Core;

class MongoQueryBuilder
{
    protected array $pipeline = [];
    protected array $filters = [];
    protected string $collection;
    
    public function __construct(string $collection)
    {
        $this->collection = $collection;
    }
    
    public function where(string $field, mixed $value): self
    {
        $this->filters[$field] = $value;
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->pipeline[] = ['$limit' => $limit];
        return $this;
    }
    
    public function sort(array $sort): self
    {
        $this->pipeline[] = ['$sort' => $sort];
        return $this;
    }
    
    public function get(): array
    {
        // MongoDB implementation would go here
        return [];
    }
    
    public function first(): ?object
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
}