<?php

namespace Ludelix\Database\Drivers;

use PDO;

/**
 * MySQL Database Driver.
 *
 * Implements database connectivity and operations for MySQL/MariaDB.
 */
class MySQLDriver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(array $config): PDO
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function getName(): string
    {
        return 'mysql';
    }

    public function getSupportedFeatures(): array
    {
        return ['transactions', 'foreign_keys'];
    }

    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string
    {
        $sql = "CREATE TABLE `{$tableName}` (\n";
        $defs = [];
        $pk = [];

        foreach ($columns as $name => $col) {
            $def = "`{$name}` " . $this->mapType($col['type']);

            if (!($col['nullable'] ?? true)) {
                $def .= " NOT NULL";
            }

            if (isset($col['default'])) {
                $def .= is_string($col['default']) ? " DEFAULT '{$col['default']}'" : " DEFAULT {$col['default']}";
            }

            if ($col['auto_increment'] ?? false) {
                $def .= " AUTO_INCREMENT";
            }

            if ($col['primary'] ?? false) {
                $pk[] = "`{$name}`";
            }

            $defs[] = $def;
        }

        if (!empty($pk)) {
            $defs[] = "PRIMARY KEY (" . implode(', ', $pk) . ")";
        }

        $sql .= implode(",\n", $defs);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        return $sql;
    }

    public function getLastInsertId(PDO $connection): string
    {
        return $connection->lastInsertId();
    }

    public function tableExists(PDO $connection, string $tableName): bool
    {
        $stmt = $connection->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetch();
    }

    public function getTableColumns(PDO $connection, string $tableName): array
    {
        $stmt = $connection->prepare("SHOW COLUMNS FROM `{$tableName}`");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    protected function mapType(string $type): string
    {
        return match ($type) {
            'int', 'integer' => 'INT',
            'string', 'varchar' => 'VARCHAR(255)',
            'text' => 'TEXT',
            'bool', 'boolean' => 'TINYINT(1)',
            'datetime' => 'DATETIME',
            'float' => 'FLOAT',
            'json' => 'JSON',
            default => 'VARCHAR(255)'
        };
    }
}