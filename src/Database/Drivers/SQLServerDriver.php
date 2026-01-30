<?php

namespace Ludelix\Database\Drivers;

use PDO;

/**
 * SQL Server Database Driver.
 *
 * Implements database connectivity and schema operations for Microsoft SQL Server.
 */
class SQLServerDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(array $config): PDO
    {
        $dsn = "sqlsrv:Server={$config['host']};Database={$config['database']}";
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getName(): string
    {
        return 'sqlserver';
    }

    public function getSupportedFeatures(): array
    {
        return ['transactions', 'foreign_keys', 'stored_procedures'];
    }

    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string
    {
        $sql = "CREATE TABLE [{$tableName}] (\n";
        $defs = [];
        $pk = [];

        foreach ($columns as $name => $col) {
            $def = "[{$name}] " . $this->mapType($col['type']);

            if ($col['auto_increment'] ?? false) {
                $def .= " IDENTITY(1,1)";
            }

            if (!($col['nullable'] ?? true)) {
                $def .= " NOT NULL";
            }

            if (isset($col['default'])) {
                $def .= " DEFAULT '{$col['default']}'";
            }

            if ($col['primary'] ?? false) {
                $pk[] = "[{$name}]";
            }

            $defs[] = $def;
        }

        if (!empty($pk)) {
            $defs[] = "PRIMARY KEY (" . implode(', ', $pk) . ")";
        }

        $sql .= implode(",\n", $defs);
        $sql .= "\n)";

        return $sql;
    }

    public function getLastInsertId(PDO $connection): string
    {
        return $connection->lastInsertId();
    }

    public function tableExists(PDO $connection, string $tableName): bool
    {
        $stmt = $connection->prepare("
            SELECT count(*) 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = 'dbo' 
            AND TABLE_NAME = ?
        ");
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetchColumn();
    }

    public function getTableColumns(PDO $connection, string $tableName): array
    {
        $stmt = $connection->prepare("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
        ");
        $stmt->execute([$tableName]);

        $columns = $stmt->fetchAll();
        return array_map(function ($col) {
            return [
                'Field' => $col['COLUMN_NAME'],
                'Type' => $col['DATA_TYPE'],
                'Null' => $col['IS_NULLABLE'],
                'Default' => $col['COLUMN_DEFAULT'],
                'Key' => '',
                'Extra' => ''
            ];
        }, $columns);
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'int', 'integer' => 'INT',
            'string', 'varchar' => 'NVARCHAR(255)',
            'text' => 'NVARCHAR(MAX)',
            'bool', 'boolean' => 'BIT',
            'datetime' => 'DATETIME2',
            'float' => 'FLOAT',
            default => 'NVARCHAR(255)'
        };
    }
}