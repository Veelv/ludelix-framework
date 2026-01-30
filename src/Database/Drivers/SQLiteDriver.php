<?php

namespace Ludelix\Database\Drivers;

use PDO;

/**
 * SQLite Database Driver.
 *
 * Implements database operations for SQLite.
 */
class SQLiteDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(array $config): PDO
    {
        return new PDO("sqlite:{$config['database']}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getName(): string
    {
        return 'sqlite';
    }

    public function getSupportedFeatures(): array
    {
        return ['transactions'];
    }

    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string
    {
        $sql = "CREATE TABLE \"{$tableName}\" (\n";
        $defs = [];
        $pk = [];

        foreach ($columns as $name => $col) {
            $def = "\"{$name}\" " . $this->mapType($col['type']);

            // SQLite specific: INTEGER PRIMARY KEY AUTOINCREMENT
            if (($col['primary'] ?? false) && ($col['auto_increment'] ?? false) && $col['type'] === 'int') {
                $def = "\"{$name}\" INTEGER PRIMARY KEY AUTOINCREMENT";
                $defs[] = $def;
                continue;
            }

            if (!($col['nullable'] ?? true)) {
                $def .= " NOT NULL";
            }

            if (isset($col['default'])) {
                $def .= is_string($col['default']) ? " DEFAULT '{$col['default']}'" : " DEFAULT {$col['default']}";
            }

            if (($col['primary'] ?? false) && !($col['auto_increment'] ?? false)) {
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
        $stmt = $connection->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name");
        $stmt->execute(['name' => $tableName]);
        return (bool) $stmt->fetch();
    }

    public function getTableColumns(PDO $connection, string $tableName): array
    {
        $stmt = $connection->prepare("PRAGMA table_info(\"{$tableName}\")");
        $stmt->execute();
        $cols = $stmt->fetchAll();

        // Map to standard format
        return array_map(function ($col) {
            return [
                'Field' => $col['name'],
                'Type' => $col['type'],
                'Null' => $col['notnull'] ? 'NO' : 'YES',
                'Default' => $col['dflt_value'],
                'Key' => $col['pk'] ? 'PRI' : '',
                'Extra' => ''
            ];
        }, $cols);
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'int', 'integer' => 'INTEGER',
            'string', 'varchar', 'text' => 'TEXT',
            'bool', 'boolean' => 'INTEGER', // SQLite uses int for bool
            'datetime' => 'TEXT',
            'float' => 'REAL',
            default => 'TEXT'
        };
    }
}