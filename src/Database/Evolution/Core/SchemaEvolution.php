<?php

namespace Ludelix\Database\Evolution\Core;

use Ludelix\Database\Evolution\Builders\TableEvolution;
use Ludelix\Database\Core\ConnectionManager;

/**
 * Base abstract class for defining Schema Evolutions (migrations).
 *
 * All PHP-based evolution classes must extend this class and implement
 * the forward() and backward() methods.
 */
abstract class SchemaEvolution
{
    /** @var string The unique identifier for this evolution. */
    public string $id;

    /** @var string A brief description of what this evolution does. */
    public string $description;

    /** @var ConnectionManager The database connection manager instance. */
    protected ConnectionManager $connectionManager;

    /**
     * Constructor.
     *
     * Dependencies like ConnectionManager are typically injected by the EvolutionManager
     * after instantiation.
     */
    public function __construct()
    {
        // Will be injected by EvolutionManager
    }

    /**
     * Executes the evolution (applies changes).
     *
     * This method contains the logic to apply the schema changes (e.g., creating tables).
     */
    abstract public function forward(): void;

    /**
     * Reverts the evolution (undoes changes).
     *
     * This method contains the logic to undo the schema changes (e.g., dropping tables).
     */
    abstract public function backward(): void;

    /**
     * Defines dependencies for this evolution.
     *
     * @return array Array with keys 'requires' and 'conflicts'.
     */
    public function dependencies(): array
    {
        return [
            'requires' => [],
            'conflicts' => []
        ];
    }

    /**
     * Helper to create a new table using the TableEvolution builder.
     *
     * @param string   $name     The name of the table to create.
     * @param callable $callback Closure that receives a TableEvolution instance.
     */
    protected function createTable(string $name, callable $callback): void
    {
        $table = new TableEvolution($name, 'create');
        $callback($table);
        $table->execute($this->connectionManager->getConnection());
    }

    /**
     * Helper to modify an existing table using the TableEvolution builder.
     *
     * @param string   $name     The name of the table to modify.
     * @param callable $callback Closure that receives a TableEvolution instance.
     */
    protected function modifyTable(string $name, callable $callback): void
    {
        $table = new TableEvolution($name, 'modify');
        $callback($table);
        $table->execute($this->connectionManager->getConnection());
    }

    /**
     * Helper to drop a table if it exists.
     *
     * @param string $name The name of the table to drop.
     */
    protected function dropTable(string $name): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("DROP TABLE IF EXISTS {$name}");
    }

    /**
     * Helper to rename a table.
     *
     * @param string $from Current table name.
     * @param string $to   New table name.
     */
    protected function renameTable(string $from, string $to): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("RENAME TABLE {$from} TO {$to}");
    }

    /**
     * Helper to add a raw index to a table.
     *
     * @param string      $table   Table name.
     * @param array       $columns Columns involved in the index.
     * @param string|null $name    Optional index name.
     */
    protected function addIndex(string $table, array $columns, string $name = null): void
    {
        $connection = $this->connectionManager->getConnection();
        $indexName = $name ?: $table . '_' . implode('_', $columns) . '_idx';
        $columnList = implode(', ', $columns);
        $connection->exec("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
    }

    /**
     * Helper to drop an index from a table.
     *
     * @param string $table Table name.
     * @param string $name  Index name.
     */
    protected function dropIndex(string $table, string $name): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec("DROP INDEX {$name} ON {$table}");
    }

    /**
     * Helper to add a foreign key constraint.
     *
     * @param string $table      The child table.
     * @param string $column     The foreign key column.
     * @param string $references The parent table/column (e.g. 'users(id)').
     * @param array  $options    Additional options like on_delete/on_update.
     */
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

    /**
     * Executes a raw SQL statement.
     *
     * @param string $sql The SQL to execute.
     */
    protected function raw(string $sql): void
    {
        $connection = $this->connectionManager->getConnection();
        $connection->exec($sql);
    }
}