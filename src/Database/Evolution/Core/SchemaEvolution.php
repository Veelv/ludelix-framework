<?php

namespace Ludelix\Database\Evolution\Core;

use Ludelix\Database\Evolution\Builders\TableEvolution;
use Ludelix\Database\Core\ConnectionManager;

abstract class SchemaEvolution
{
    public string $id;
    public string $description;
    protected ConnectionManager $connectionManager;

    public function __construct()
    {
        // Will be injected by EvolutionManager
    }

    abstract public function forward(): void;
    abstract public function backward(): void;

    public function dependencies(): array
    {
        return [
            'requires' => [],
            'conflicts' => []
        ];
    }

    protected function createTable(string $name, callable $callback): void
    {
        $table = new TableEvolution($name, 'create');
        $callback($table);
        $table->execute($this->connectionManager->getConnection());
    }

    protected function modifyTable(string $name, callable $callback): void
    {
        $table = new TableEvolution($name, 'modify');
        $callback($table);
        $table->execute($this->connectionManager->getConnection());
    }

    protected function dropTable(string $name): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("DROP TABLE IF EXISTS {$name}");
    }

    protected function renameTable(string $from, string $to): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("RENAME TABLE {$from} TO {$to}");
    }

    protected function addIndex(string $table, array $columns, string $name = null): void
    {
        $connection = $this->connectionManager->getConnection();
        $indexName = $name ?: $table . '_' . implode('_', $columns) . '_idx';
        $columnList = implode(', ', $columns);
        $connection->exec("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
    }

    protected function dropIndex(string $table, string $name): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("DROP INDEX {$name} ON {$table}");
    }

    protected function addForeignKey(string $table, string $column, string $references, array $options = []): void
    {
        $connection = $this->connectionManager->getConnection();
        $constraintName = $options['name'] ?? $table . '_' . $column . '_fk';
        $onDelete = $options['on_delete'] ?? 'RESTRICT';
        $onUpdate = $options['on_update'] ?? 'RESTRICT';
        
        $sql = "ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} 
                FOREIGN KEY ({$column}) REFERENCES {$references} 
                ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
        
        $connection->exec($sql);
    }

    protected function raw(string $sql): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec($sql);
    }
}