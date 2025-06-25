<?php

namespace Ludelix\Database\Drivers;

use PDO;

class MySQLDriver implements DriverInterface
{
    public function connect(array $config): PDO
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    
    public function getName(): string { return 'mysql'; }
    public function getSupportedFeatures(): array { return ['transactions', 'foreign_keys']; }
    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string { return ""; }
    public function getLastInsertId(PDO $connection): string { return $connection->lastInsertId(); }
    public function tableExists(PDO $connection, string $tableName): bool { return true; }
    public function getTableColumns(PDO $connection, string $tableName): array { return []; }
}