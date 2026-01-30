<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ludelix\Database\Drivers\MySQLDriver;
use Ludelix\Database\Drivers\PgSQLDriver;
use Ludelix\Database\Drivers\SQLServerDriver;
use Ludelix\Database\Drivers\SQLiteDriver;
use Ludelix\Database\Drivers\MongoDBDriver;

echo "\n--- Ludelix Drivers Verification ---\n";

function testDriver($name, $driverClass, $expectedSqlPattern = null)
{
    echo "\nTesting Driver: {$name}\n";
    try {
        $driver = new $driverClass();
        echo "[OK] Instantiated {$driverClass}\n";

        $features = $driver->getSupportedFeatures();
        echo "[OK] Features: " . implode(', ', $features) . "\n";

        if ($expectedSqlPattern !== null) {
            $tableName = 'users';
            $columns = [
                'id' => ['type' => 'int', 'auto_increment' => true, 'primary' => true],
                'name' => ['type' => 'string', 'nullable' => false],
                'active' => ['type' => 'bool', 'default' => true],
                'data' => ['type' => 'json', 'nullable' => true],
            ];

            echo "Testing SQL Generation...\n";
            $sql = $driver->buildCreateTableSql($tableName, $columns);

            // Normalize whitespace
            $sql = preg_replace('/\s+/', ' ', $sql);

            if ($sql === '') {
                echo "[OK] SQL Generation (NoSQL/Expected Empty)\n";
            } else {
                echo "\nGenerated SQL:\n" . str_repeat('-', 20) . "\n" . trim($sql) . "\n" . str_repeat('-', 20) . "\n";

                if (preg_match($expectedSqlPattern, $sql)) {
                    echo "[OK] SQL matches pattern.\n";
                } else {
                    echo "[FAIL] SQL does NOT match pattern.\n";
                    echo "Pattern: {$expectedSqlPattern}\n";
                }
            }
        }
    } catch (\Throwable $e) {
        echo "[ERROR] " . $e->getMessage() . "\n";
    }
}

// MySQL
testDriver('MySQL', MySQLDriver::class, '/CREATE TABLE `users`.*`id` INT.*AUTO_INCREMENT.*PRIMARY KEY/i');

// PostgreSQL
testDriver('PostgreSQL', PgSQLDriver::class, '/CREATE TABLE "users".*"id" SERIAL.*PRIMARY KEY/i');

// SQL Server
testDriver('SQL Server', SQLServerDriver::class, '/CREATE TABLE \[users\].*\[id\].*IDENTITY\(1,1\).*PRIMARY KEY/i');

// SQLite
testDriver('SQLite', SQLiteDriver::class, '/CREATE TABLE "users".*"id" INTEGER PRIMARY KEY AUTOINCREMENT/i');

// MongoDB
testDriver('MongoDB', MongoDBDriver::class, ''); // Expect empty SQL

echo "\n--- Drivers Verification Complete ---\n";
