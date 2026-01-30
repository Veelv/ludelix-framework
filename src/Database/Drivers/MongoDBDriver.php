<?php

namespace Ludelix\Database\Drivers;

use MongoDB\Client;
use PDO;
use RuntimeException;

/**
 * MongoDB Driver.
 *
 * Implements connectivity for MongoDB using the mongodb extension.
 * Note: Does not implement DriverInterface strictly if strict typing for PDO is enforced elsewhere,
 * but structurally adapts to the framework.
 */
class MongoDBDriver
{
    /**
     * Connects to the MongoDB database.
     *
     * @param array $config
     * @return Client
     */
    public function connect(array $config): Client
    {
        if (!class_exists(Client::class)) {
            throw new RuntimeException("MongoDB driver not installed. Please run: composer require mongodb/mongodb");
        }

        $dsn = "mongodb://";

        if (!empty($config['username']) && !empty($config['password'])) {
            $dsn .= "{$config['username']}:{$config['password']}@";
        }

        $dsn .= "{$config['host']}:{$config['port']}";

        if (!empty($config['database'])) {
            $dsn .= "/{$config['database']}";
        }

        return new Client($dsn, [
            'appname' => 'LudelixFramework'
        ]);
    }

    public function getName(): string
    {
        return 'mongodb';
    }

    public function getSupportedFeatures(): array
    {
        return ['collections', 'embedded_documents'];
    }

    // NoSQL does not use SQL for table creation
    public function buildCreateTableSql(string $tableName, array $columns, array $options = []): string
    {
        return '';
    }

    public function getLastInsertId($connection): string
    {
        return ''; // MongoDB ObjectIDs are generated client-side or returned in InsertOneResult
    }

    public function tableExists($connection, string $tableName): bool
    {
        // $connection is MongoDB\Client
        $databaseName = 'default'; // Should be passed or retrieved from config
        // Basic check stub
        return false;
    }

    public function getTableColumns($connection, string $tableName): array
    {
        return []; // Schema-less
    }
}