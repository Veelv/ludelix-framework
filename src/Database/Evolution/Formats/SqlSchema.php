<?php

namespace Ludelix\Database\Evolution\Formats;

class SqlSchema
{
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

    protected function exportIndex(string $table, array $index): string
    {
        $indexType = $index['Non_unique'] == 0 ? 'UNIQUE INDEX' : 'INDEX';
        return "CREATE {$indexType} `{$index['Key_name']}` ON `{$table}` (`{$index['Column_name']}`);\n";
    }

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