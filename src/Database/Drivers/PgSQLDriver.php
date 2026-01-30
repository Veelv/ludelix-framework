<?php

namespace Ludelix\Database\Drivers;

use PDO;

/**
 * PostgreSQL Database Driver.
 *
 * Implements database connectivity and schema operations for PostgreSQL.
 */
class PgSQLDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(array $config): PDO
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getName(): string
    {
        return 'pgsql';
    }

    public function getSupportedFeatures(): array
    {
        return ['transactions', 'foreign_keys', 'json', 'sequences'];
    }

    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string
    {
        $sql = "CREATE TABLE \"{$tableName}\" (\n";
        $defs = [];
        $pk = [];

        foreach ($columns as $name => $col) {
            $def = "\"{$name}\" " . $this->mapType($col['type']);

            if (!($col['nullable'] ?? true)) {
                $def .= " NOT NULL";
            }

            if (isset($col['default'])) {
                $def .= " DEFAULT {$col['default']}";
            }

            if ($col['auto_increment'] ?? false) {
                $def = "\"{$name}\" SERIAL"; // Override type for serial
            }

            if ($col['primary'] ?? false) {
                $pk[] = "\"{$name}\"";
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
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )
        ");
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetchColumn();
    }

    public function getTableColumns(PDO $connection, string $tableName): array
    {
        $stmt = $connection->prepare("
            SELECT column_name as \"Field\", 
                   data_type as \"Type\", 
                   is_nullable as \"Null\", 
                   column_default as \"Default\"
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = ?
        ");
        $stmt->execute([$tableName]);

        $columns = $stmt->fetchAll();
        return array_map(function ($col) {
            return [
                'Field' => $col['Field'],
                'Type' => $col['Type'],
                'Null' => $col['Null'] === 'YES' ? 'YES' : 'NO',
                'Default' => $col['Default'],
                'Key' => '', // Difficult to get strictly from information_schema without joins
                'Extra' => ''
            ];
        }, $columns);
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'int', 'integer' => 'INTEGER',
            'string', 'varchar' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'bool', 'boolean' => 'BOOLEAN',
            'datetime' => 'TIMESTAMP',
            'float' => 'DOUBLE PRECISION',
            'json' => 'JSONB',
            default => 'VARCHAR(255)'
        };
    }
}