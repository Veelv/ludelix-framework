<?php

namespace Ludelix\Database\Core;

/**
 * Simple SQL query logger for debugging and profiling.
 */
class Logger
{
    /** @var array Logged queries */
    protected array $queries = [];

    /**
     * Logs a executed query.
     *
     * @param string $sql
     * @param array  $bindings
     * @param float  $time
     */
    public function logQuery(string $sql, array $bindings = [], float $time = 0): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Returns all logged queries.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * Clears the query log.
     */
    public function clear(): void
    {
        $this->queries = [];
    }
}