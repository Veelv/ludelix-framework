<?php

namespace Ludelix\Database\Core;

class Logger
{
    protected array $queries = [];
    
    public function logQuery(string $sql, array $bindings = [], float $time = 0): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
    }
    
    public function getQueries(): array
    {
        return $this->queries;
    }
    
    public function clear(): void
    {
        $this->queries = [];
    }
}