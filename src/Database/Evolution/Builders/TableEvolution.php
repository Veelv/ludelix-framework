<?php

namespace Ludelix\Database\Evolution\Builders;

use PDO;

/**
 * Flexible Table Builder for schema changes.
 *
 * Provides a fluent interface for defining, modifying, and dropping database tables.
 * Supports defining columns, indexes, and constraints.
 */
class TableEvolution
{
    protected string $tableName;
    protected string $action;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $constraints = [];
    protected array $modifications = [];

    /**
     * Initializes the table evolution builder.
     *
     * @param string $tableName The name of the table.
     * @param string $action    The action to perform ('create' or 'modify').
     */
    public function __construct(string $tableName, string $action = 'create')
    {
        $this->tableName = $tableName;
        $this->action = $action;
    }

    /**
     * Adds an auto-incrementing primary key column (usually 'id').
     *
     * @param string $name The column name (default: 'id').
     * @return ColumnDefinition
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->bigInteger($name)->autoIncrement()->primary();
    }

    /**
     * Adds a VARCHAR column.
     *
     * @param string $name   The column name.
     * @param int    $length The maximum length (default: 255).
     * @return ColumnDefinition
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'VARCHAR', ['length' => $length]);
    }

    /**
     * Adds a TEXT column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }

    /**
     * Adds an INTEGER column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT');
    }

    /**
     * Adds a BIGINT column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT');
    }

    /**
     * Adds a DECIMAL column.
     *
     * @param string $name      The column name.
     * @param int    $precision Total number of digits.
     * @param int    $scale     Number of digits after the decimal point.
     * @return ColumnDefinition
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'DECIMAL', ['precision' => $precision, 'scale' => $scale]);
    }

    /**
     * Adds a BOOLEAN column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BOOLEAN');
    }

    /**
     * Adds a DATE column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE');
    }

    /**
     * Adds a DATETIME column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME');
    }

    /**
     * Adds a TIMESTAMP column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    /**
     * Adds an email column (VARCHAR with implicit check).
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function email(string $name): ColumnDefinition
    {
        return $this->string($name)->check("email LIKE '%@%'");
    }

    /**
     * Adds a password column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function password(string $name): ColumnDefinition
    {
        return $this->string($name, 255);
    }

    /**
     * Adds a JSON column.
     *
     * @param string $name The column name.
     * @return ColumnDefinition
     */
    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'JSON');
    }

    /**
     * Adds standard 'created_at' and 'updated_at' timestamps.
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    /**
     * Adds a 'remember_token' column for authentication.
     *
     * @return ColumnDefinition
     */
    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Adds an index to the table.
     *
     * @param array       $columns Array of column names to index.
     * @param string|null $name    Optional custom index name.
     * @return self
     */
    public function index(array $columns, string $name = null): self
    {
        $indexName = $name ?: $this->tableName . '_' . implode('_', $columns) . '_idx';
        $this->indexes[] = [
            'name' => $indexName,
            'columns' => $columns,
            'unique' => false
        ];
        return $this;
    }

    /**
     * Adds a unique index to the table.
     *
     * @param array       $columns Array of column names.
     * @param string|null $name    Optional custom index name.
     * @return self
     */
    public function unique(array $columns, string $name = null): self
    {
        $indexName = $name ?: $this->tableName . '_' . implode('_', $columns) . '_unique';
        $this->indexes[] = [
            'name' => $indexName,
            'columns' => $columns,
            'unique' => true
        ];
        return $this;
    }

    /**
     * Adds a CHECK constraint to the table.
     *
     * @param string $name      Constraint name.
     * @param string $condition SQL condition.
     * @return self
     */
    public function check(string $name, string $condition): self
    {
        $this->constraints[] = [
            'type' => 'check',
            'name' => $name,
            'condition' => $condition
        ];
        return $this;
    }

    /**
     * Adds a foreign key constraint.
     *
     * @param string $column     Local column name.
     * @param string $references Referenced table/column (e.g., 'users(id)').
     * @param array  $options    Additional options (onUpdate, onDelete).
     * @return self
     */
    public function foreign(string $column, string $references, array $options = []): self
    {
        $this->constraints[] = [
            'type' => 'foreign',
            'column' => $column,
            'references' => $references,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Registers a new column to be added.
     *
     * @param string $name    Column name.
     * @param string $type    Column type.
     * @param array  $options Additional options.
     * @return ColumnDefinition
     */
    public function addColumn(string $name, string $type, array $options = []): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $options);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Schedules a column to be dropped.
     *
     * @param string $name Column name.
     * @return self
     */
    public function dropColumn(string $name): self
    {
        $this->modifications[] = ['action' => 'drop_column', 'column' => $name];
        return $this;
    }

    /**
     * Schedules a column to be renamed.
     *
     * @param string $from Current column name.
     * @param string $to   New column name.
     * @return self
     */
    public function renameColumn(string $from, string $to): self
    {
        $this->modifications[] = ['action' => 'rename_column', 'from' => $from, 'to' => $to];
        return $this;
    }

    /**
     * Executes the planned table evolution (create or modify).
     *
     * @param PDO $connection The database connection.
     */
    public function execute(PDO $connection): void
    {
        if ($this->action === 'create') {
            $this->executeCreate($connection);
        } elseif ($this->action === 'modify') {
            $this->executeModify($connection);
        }
    }

    /**
     * Executes table creation logic.
     *
     * @param PDO $connection
     */
    protected function executeCreate(PDO $connection): void
    {
        $columnDefinitions = [];

        foreach ($this->columns as $column) {
            $columnDefinitions[] = $column->toSql();
        }

        $sql = "CREATE TABLE {$this->tableName} (" . implode(', ', $columnDefinitions) . ")";
        $connection->exec($sql);

        // Add indexes
        foreach ($this->indexes as $index) {
            $this->createIndex($connection, $index);
        }

        // Add constraints
        foreach ($this->constraints as $constraint) {
            $this->createConstraint($connection, $constraint);
        }
    }

    /**
     * Executes table modification logic.
     *
     * @param PDO $connection
     */
    protected function executeModify(PDO $connection): void
    {
        // Add new columns
        foreach ($this->columns as $column) {
            $sql = "ALTER TABLE {$this->tableName} ADD COLUMN " . $column->toSql();
            $connection->exec($sql);
        }

        // Execute modifications
        foreach ($this->modifications as $mod) {
            match ($mod['action']) {
                'drop_column' => $connection->exec("ALTER TABLE {$this->tableName} DROP COLUMN {$mod['column']}"),
                'rename_column' => $connection->exec("ALTER TABLE {$this->tableName} RENAME COLUMN {$mod['from']} TO {$mod['to']}"),
                default => null
            };
        }
    }

    /**
     * Creates an index on the table.
     *
     * @param PDO   $connection
     * @param array $index
     */
    protected function createIndex(PDO $connection, array $index): void
    {
        $type = $index['unique'] ? 'UNIQUE INDEX' : 'INDEX';
        $columns = implode(', ', $index['columns']);
        $sql = "CREATE {$type} {$index['name']} ON {$this->tableName} ({$columns})";
        $connection->exec($sql);
    }

    /**
     * Creates a constraint on the table.
     *
     * @param PDO   $connection
     * @param array $constraint
     */
    protected function createConstraint(PDO $connection, array $constraint): void
    {
        if ($constraint['type'] === 'check') {
            $sql = "ALTER TABLE {$this->tableName} ADD CONSTRAINT {$constraint['name']} CHECK ({$constraint['condition']})";
            $connection->exec($sql);
        }
    }
}