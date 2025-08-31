<?php

namespace Ludelix\Database\Evolution\Builders;

use PDO;

class TableEvolution
{
    protected string $tableName;
    protected string $action;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $constraints = [];
    protected array $modifications = [];

    public function __construct(string $tableName, string $action = 'create')
    {
        $this->tableName = $tableName;
        $this->action = $action;
    }

    // Column types
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->bigInteger($name)->autoIncrement()->primary();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'VARCHAR', ['length' => $length]);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT');
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT');
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'DECIMAL', ['precision' => $precision, 'scale' => $scale]);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BOOLEAN');
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE');
    }

    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    public function email(string $name): ColumnDefinition
    {
        return $this->string($name)->check("email LIKE '%@%'");
    }

    public function password(string $name): ColumnDefinition
    {
        return $this->string($name, 255);
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'JSON');
    }

    // Timestamps
    public function timestamps(): void
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdate('CURRENT_TIMESTAMP');
    }

    public function rememberToken(): ColumnDefinition
    {
        return $this->string('remember_token', 100)->nullable();
    }

    // Indexes
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

    // Constraints
    public function check(string $name, string $condition): self
    {
        $this->constraints[] = [
            'type' => 'check',
            'name' => $name,
            'condition' => $condition
        ];
        return $this;
    }

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

    // Modifications (for alter table)
    public function addColumn(string $name, string $type, array $options = []): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $options);
        $this->columns[] = $column;
        return $column;
    }

    public function dropColumn(string $name): self
    {
        $this->modifications[] = ['action' => 'drop_column', 'column' => $name];
        return $this;
    }

    public function renameColumn(string $from, string $to): self
    {
        $this->modifications[] = ['action' => 'rename_column', 'from' => $from, 'to' => $to];
        return $this;
    }

    // Execute the table evolution
    public function execute(PDO $connection): void
    {
        if ($this->action === 'create') {
            $this->executeCreate($connection);
        } elseif ($this->action === 'modify') {
            $this->executeModify($connection);
        }
    }

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

    protected function executeModify(PDO $connection): void
    {
        // Add new columns
        foreach ($this->columns as $column) {
            $sql = "ALTER TABLE {$this->tableName} ADD COLUMN " . $column->toSql();
            $connection->exec($sql);
        }

        // Execute modifications
        foreach ($this->modifications as $mod) {
            match($mod['action']) {
                'drop_column' => $connection->exec("ALTER TABLE {$this->tableName} DROP COLUMN {$mod['column']}"),
                'rename_column' => $connection->exec("ALTER TABLE {$this->tableName} RENAME COLUMN {$mod['from']} TO {$mod['to']}"),
                default => null
            };
        }
    }

    protected function createIndex(PDO $connection, array $index): void
    {
        $type = $index['unique'] ? 'UNIQUE INDEX' : 'INDEX';
        $columns = implode(', ', $index['columns']);
        $sql = "CREATE {$type} {$index['name']} ON {$this->tableName} ({$columns})";
        $connection->exec($sql);
    }

    protected function createConstraint(PDO $connection, array $constraint): void
    {
        if ($constraint['type'] === 'check') {
            $sql = "ALTER TABLE {$this->tableName} ADD CONSTRAINT {$constraint['name']} CHECK ({$constraint['condition']})";
            $connection->exec($sql);
        }
    }
}