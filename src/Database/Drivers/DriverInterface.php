<?php

namespace Ludelix\Database\Drivers;

use PDO;

interface DriverInterface
{
    public function connect(array $config): PDO;
    public function getName(): string;
    public function getSupportedFeatures(): array;
    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string;
    public function getLastInsertId(PDO $connection): string;
    public function tableExists(PDO $connection, string $tableName): bool;
    public function getTableColumns(PDO $connection, string $tableName): array;
}