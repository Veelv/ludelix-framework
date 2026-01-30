<?php

namespace Ludelix\Database\Evolution\Formats;

/**
 * Handles SQL format schema export and generation.
 *
 * Converts internal schema definitions into standard SQL DDL statements (CREATE TABLE, etc.).
 * and supports exporting the entire database schema as a raw SQL dump.
 */
class SqlSchema
{
    /**
     * Exports the entire schema definition to a SQL dump string.
     *
     * Includes comments, metadata, and disables foreign key checks during the operation.
     *
     * @param array $schema The complete schema definition array.
     * @return string The generated SQL dump.
     */
    public function export(array $schema): string
    {
        $sql = "-- Database Schema Export\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($schema as $table => $definition) {
            $sql .= $this->exportTable($table, $definition);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        return $sql;
    }

    /**
     * Generates SQL to recreate a specific table from its definition.
     *
     * Includes DROP TABLE IF EXISTS, CREATE TABLE, and index creation statements.
     *
     * @param string $table      The name of the table.
     * @param array  $definition The table definition array (columns, indexes, etc.).
     * @return string The SQL statements for the table.
     */
    public function exportTable(string $table, array $definition): string
    {
        $sql = "-- Table: {$table}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= "CREATE TABLE `{$table}` (\n";

        $columns = [];
        $primaryKeys = [];

        foreach ($definition['columns'] as $column) {
            $columnSql = $this->exportColumn($column);
            $columns[] = $columnSql;

            if ($column['Key'] === 'PRI') {
                $primaryKeys[] = "`{$column['Field']}`";
            }
        }

        $sql .= "  " . implode(",\n  ", $columns);

        if (!empty($primaryKeys)) {
            $sql .= ",\n  PRIMARY KEY (" . implode(', ', $primaryKeys) . ")";
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";

        // Add indexes
        foreach ($definition['indexes'] as $index) {
            if ($index['Key_name'] !== 'PRIMARY') {
                $sql .= $this->exportIndex($table, $index);
            }
        }

        return $sql;
    }

    /**
     * Generates SQL definition for a single column.
     *
     * @param array $column The column definition array from `DESCRIBE`.
     * @return string The SQL fragment for the column.
     */
    protected function exportColumn(array $column): string
    {
        $sql = "`{$column['Field']}` {$column['Type']}";

        if ($column['Null'] === 'NO') {
            $sql .= ' NOT NULL';
        }

        if ($column['Default'] !== null) {
            if (in_array(strtoupper($column['Default']), ['CURRENT_TIMESTAMP', 'NULL'])) {
                $sql .= " DEFAULT {$column['Default']}";
            } else {
                $sql .= " DEFAULT '{$column['Default']}'";
            }
        }

        if ($column['Extra']) {
            $sql .= ' ' . strtoupper($column['Extra']);
        }

        return $sql;
    }

    /**
     * Generates SQL to create an index.
     *
     * @param string $table The table name.
     * @param array  $index The index definition array from `SHOW INDEX`.
     * @return string The CREATE INDEX SQL statement.
     */
    protected function exportIndex(string $table, array $index): string
    {
        $indexType = $index['Non_unique'] == 0 ? 'UNIQUE INDEX' : 'INDEX';
        return "CREATE {$indexType} `{$index['Key_name']}` ON `{$table}` (`{$index['Column_name']}`);\n";
    }

    /**
     * Generates a CREATE TABLE statement from a simplified column map.
     *
     * @param string $table   The table name.
     * @param array  $columns Array of column definitions keyed by name.
     * @param array  $options Table options (engine, charset, primary_key).
     * @return string The CREATE TABLE SQL statement.
     */
    public function generateCreateTable(string $table, array $columns, array $options = []): string
    {
        $sql = "CREATE TABLE `{$table}` (\n";

        $columnDefinitions = [];
        foreach ($columns as $name => $definition) {
            $columnDefinitions[] = $this->buildColumnDefinition($name, $definition);
        }

        $sql .= "  " . implode(",\n  ", $columnDefinitions);

        if (isset($options['primary_key'])) {
            $primaryKeys = is_array($options['primary_key']) ? $options['primary_key'] : [$options['primary_key']];
            $sql .= ",\n  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }

        $sql .= "\n) ENGINE=" . ($options['engine'] ?? 'InnoDB');
        $sql .= " DEFAULT CHARSET=" . ($options['charset'] ?? 'utf8mb4');
        $sql .= " COLLATE=" . ($options['collate'] ?? 'utf8mb4_unicode_ci') . ";\n";

        return $sql;
    }

    /**
     * Builds the SQL fragment for a column definition from the abstract schema format.
     *
     * @param string $name       Column name.
     * @param array  $definition Schema definition details (type, length, etc.).
     * @return string SQL column definition string.
     */
    protected function buildColumnDefinition(string $name, array $definition): string
    {
        $sql = "`{$name}` " . strtoupper($definition['type']);

        if (isset($definition['length'])) {
            $sql .= "({$definition['length']})";
        }

        if (!($definition['nullable'] ?? true)) {
            $sql .= ' NOT NULL';
        }

        if (isset($definition['default'])) {
            $sql .= " DEFAULT '{$definition['default']}'";
        }

        if ($definition['auto_increment'] ?? false) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }
}