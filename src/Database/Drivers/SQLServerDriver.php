<?php

namespace Ludelix\Database\Drivers;

use PDO;

class SQLServerDriver implements DriverInterface
{
    public function connect(array $config): PDO
    {
        $dsn = "sqlsrv:Server={$config['host']};Database={$config['database']}";
        return new PDO($dsn, $config['username'], $config['password']);
    }
    
    public function getName(): string { return 'sqlserver'; }
    public function getSupportedFeatures(): array { return ['transactions']; }
    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string { return ""; }
    public function getLastInsertId(PDO $connection): string { return $connection->lastInsertId(); }
    public function tableExists(PDO $connection, string $tableName): bool { return true; }
    public function getTableColumns(PDO $connection, string $tableName): array { return []; }
}