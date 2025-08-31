<?php

namespace Ludelix\Database\Evolution\Snapshots;

use Ludelix\Database\Core\ConnectionManager;

class DiffEngine
{
    protected ConnectionManager $connectionManager;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function compare(array $schema1, array $schema2): array
    {
        $diff = [
            'added_tables' => [],
            'removed_tables' => [],
            'modified_tables' => []
        ];

        $tables1 = array_keys($schema1);
        $tables2 = array_keys($schema2);

        // Find added tables
        $diff['added_tables'] = array_diff($tables2, $tables1);

        // Find removed tables
        $diff['removed_tables'] = array_diff($tables1, $tables2);

        // Find modified tables
        $common_tables = array_intersect($tables1, $tables2);
        foreach ($common_tables as $table) {
            $tableDiff = $this->compareTable($schema1[$table], $schema2[$table]);
            if (!empty($tableDiff)) {
                $diff['modified_tables'][$table] = $tableDiff;
            }
        }

        return $diff;
    }

    public function compareWithCurrent(array $snapshotSchema): array
    {
        $connection = $this->connectionManager->getConnection();
        $currentSchema = $this->captureCurrentSchema($connection);
        
        return $this->compare($snapshotSchema, $currentSchema);
    }

    public function generateSql(array $diff): array
    {
        $sql = [];

        // Drop removed tables
        foreach ($diff['removed_tables'] as $table) {
            $sql[] = "DROP TABLE IF EXISTS {$table};";
        }

        // Create added tables
        foreach ($diff['added_tables'] as $table) {
            $sql[] = "-- CREATE TABLE {$table} (definition needed);";
        }

        // Modify existing tables
        foreach ($diff['modified_tables'] as $table => $changes) {
            $sql = array_merge($sql, $this->generateTableModificationSql($table, $changes));
        }

        return $sql;
    }

    protected function compareTable(array $table1, array $table2): array
    {
        $diff = [];

        // Compare columns
        $columnDiff = $this->compareColumns($table1['columns'], $table2['columns']);
        if (!empty($columnDiff)) {
            $diff['columns'] = $columnDiff;
        }

        // Compare indexes
        $indexDiff = $this->compareIndexes($table1['indexes'], $table2['indexes']);
        if (!empty($indexDiff)) {
            $diff['indexes'] = $indexDiff;
        }

        return $diff;
    }

    protected function compareColumns(array $columns1, array $columns2): array
    {
        $diff = [
            'added' => [],
            'removed' => [],
            'modified' => []
        ];

        $cols1 = $this->indexColumnsByName($columns1);
        $cols2 = $this->indexColumnsByName($columns2);

        $names1 = array_keys($cols1);
        $names2 = array_keys($cols2);

        // Added columns
        $diff['added'] = array_diff($names2, $names1);

        // Removed columns
        $diff['removed'] = array_diff($names1, $names2);

        // Modified columns
        $common = array_intersect($names1, $names2);
        foreach ($common as $name) {
            if ($this->columnsAreDifferent($cols1[$name], $cols2[$name])) {
                $diff['modified'][$name] = [
                    'from' => $cols1[$name],
                    'to' => $cols2[$name]
                ];
            }
        }

        return array_filter($diff);
    }

    protected function compareIndexes(array $indexes1, array $indexes2): array
    {
        $diff = [
            'added' => [],
            'removed' => []
        ];

        $idx1 = $this->indexesByName($indexes1);
        $idx2 = $this->indexesByName($indexes2);

        $names1 = array_keys($idx1);
        $names2 = array_keys($idx2);

        $diff['added'] = array_diff($names2, $names1);
        $diff['removed'] = array_diff($names1, $names2);

        return array_filter($diff);
    }

    protected function indexColumnsByName(array $columns): array
    {
        $indexed = [];
        foreach ($columns as $column) {
            $indexed[$column['Field']] = $column;
        }
        return $indexed;
    }

    protected function indexesByName(array $indexes): array
    {
        $indexed = [];
        foreach ($indexes as $index) {
            $indexed[$index['Key_name']] = $index;
        }
        return $indexed;
    }

    protected function columnsAreDifferent(array $col1, array $col2): bool
    {
        $compareFields = ['Type', 'Null', 'Default', 'Extra'];
        
        foreach ($compareFields as $field) {
            if (($col1[$field] ?? null) !== ($col2[$field] ?? null)) {
                return true;
            }
        }
        
        return false;
    }

    protected function generateTableModificationSql(string $table, array $changes): array
    {
        $sql = [];

        if (isset($changes['columns'])) {
            // Add columns
            foreach ($changes['columns']['added'] ?? [] as $column) {
                $sql[] = "ALTER TABLE {$table} ADD COLUMN {$column} (definition needed);";
            }

            // Drop columns
            foreach ($changes['columns']['removed'] ?? [] as $column) {
                $sql[] = "ALTER TABLE {$table} DROP COLUMN {$column};";
            }

            // Modify columns
            foreach ($changes['columns']['modified'] ?? [] as $column => $change) {
                $sql[] = "ALTER TABLE {$table} MODIFY COLUMN {$column} (new definition needed);";
            }
        }

        if (isset($changes['indexes'])) {
            // Drop indexes
            foreach ($changes['indexes']['removed'] ?? [] as $index) {
                $sql[] = "DROP INDEX {$index} ON {$table};";
            }

            // Add indexes
            foreach ($changes['indexes']['added'] ?? [] as $index) {
                $sql[] = "CREATE INDEX {$index} ON {$table} (columns needed);";
            }
        }

        return $sql;
    }

    protected function captureCurrentSchema($connection): array
    {
        $schema = [];
        $stmt = $connection->query("SHOW TABLES");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $schema[$table] = [
                'columns' => $this->getTableColumns($connection, $table),
                'indexes' => $this->getTableIndexes($connection, $table),
                'constraints' => []
            ];
        }
        
        return $schema;
    }

    protected function getTableColumns($connection, string $table): array
    {
        $stmt = $connection->query("DESCRIBE {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function getTableIndexes($connection, string $table): array
    {
        $stmt = $connection->query("SHOW INDEX FROM {$table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}