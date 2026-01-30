<?php

namespace Ludelix\Tenant\Provisioning;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Database\Core\ConnectionManager;

/**
 * Database Provisioner - Tenant Database Setup Manager
 * 
 * Handles database provisioning for tenants including schema creation,
 * table setup, user permissions, and database isolation configuration.
 * 
 * @package Ludelix\Tenant\Provisioning
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class DatabaseProvisioner
{
    /**
     * Database connection manager
     */
    protected ConnectionManager $connectionManager;

    /**
     * Provisioning configuration
     */
    protected array $config;

    /**
     * Provisioned databases tracking
     */
    protected array $provisionedDatabases = [];

    /**
     * Initialize database provisioner
     * 
     * @param ConnectionManager $connectionManager Database connection manager
     * @param array $config Provisioning configuration
     */
    public function __construct(ConnectionManager $connectionManager, array $config = [])
    {
        $this->connectionManager = $connectionManager;
        $this->config = array_merge([
            'default_strategy' => 'prefix',
            'create_user' => false,
            'run_migrations' => true,
            'seed_data' => false,
        ], $config);
    }

    /**
     * Provision database resources for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     * @throws \Exception If provisioning fails
     */
    public function provision(TenantInterface $tenant, array $options = []): bool
    {
        $dbConfig = $tenant->getDatabaseConfig();
        $strategy = $dbConfig['strategy'] ?? $this->config['default_strategy'];

        try {
            switch ($strategy) {
                case 'separate':
                    return $this->provisionSeparateDatabase($tenant, $options);
                case 'schema':
                    return $this->provisionSchema($tenant, $options);
                case 'prefix':
                    return $this->provisionWithPrefix($tenant, $options);
                default:
                    throw new \InvalidArgumentException("Unknown database strategy: {$strategy}");
            }
        } catch (\Throwable $e) {
            throw new \Exception("Database provisioning failed for tenant {$tenant->getId()}: " . $e->getMessage());
        }
    }

    /**
     * Deprovision database resources for tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function deprovision(string $tenantId): bool
    {
        if (!isset($this->provisionedDatabases[$tenantId])) {
            return true; // Already deprovisioned
        }

        $config = $this->provisionedDatabases[$tenantId];
        $strategy = $config['strategy'];

        try {
            switch ($strategy) {
                case 'separate':
                    return $this->deprovisionSeparateDatabase($tenantId, $config);
                case 'schema':
                    return $this->deprovisionSchema($tenantId, $config);
                case 'prefix':
                    return $this->deprovisionWithPrefix($tenantId, $config);
                default:
                    return false;
            }
        } catch (\Throwable $e) {
            error_log("Database deprovisioning failed for tenant {$tenantId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database provisioning status
     * 
     * @param string $tenantId Tenant identifier
     * @return array Status information
     */
    public function getStatus(string $tenantId): array
    {
        if (!isset($this->provisionedDatabases[$tenantId])) {
            return [
                'status' => 'not_provisioned',
                'message' => 'Database not provisioned',
                'strategy' => null,
            ];
        }

        $config = $this->provisionedDatabases[$tenantId];
        
        return [
            'status' => 'ready',
            'message' => 'Database provisioned successfully',
            'strategy' => $config['strategy'],
            'connection' => $config['connection'] ?? 'default',
            'provisioned_at' => $config['provisioned_at'],
        ];
    }

    /**
     * Rollback database provisioning
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function rollback(string $tenantId): bool
    {
        return $this->deprovision($tenantId);
    }

    /**
     * Provision separate database for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     */
    protected function provisionSeparateDatabase(TenantInterface $tenant, array $options): bool
    {
        $connection = $this->connectionManager->getConnection();
        $dbConfig = $tenant->getDatabaseConfig();
        $databaseName = $dbConfig['database'] ?? $tenant->getId() . '_db';

        // Create database
        $connection->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Create database user if configured
        if ($this->config['create_user']) {
            $this->createDatabaseUser($tenant, $databaseName);
        }

        // Run migrations if configured
        if ($this->config['run_migrations']) {
            $this->runMigrations($tenant, $databaseName);
        }

        // Seed data if configured
        if ($this->config['seed_data']) {
            $this->seedDatabase($tenant, $databaseName);
        }

        // Track provisioned database
        $this->provisionedDatabases[$tenant->getId()] = [
            'strategy' => 'separate',
            'database' => $databaseName,
            'connection' => $dbConfig['connection'] ?? 'default',
            'provisioned_at' => date('Y-m-d H:i:s'),
        ];

        return true;
    }

    /**
     * Provision schema for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     */
    protected function provisionSchema(TenantInterface $tenant, array $options): bool
    {
        $connection = $this->connectionManager->getConnection();
        $dbConfig = $tenant->getDatabaseConfig();
        $schemaName = $dbConfig['schema'] ?? $tenant->getId();

        // Create schema
        $connection->exec("CREATE SCHEMA IF NOT EXISTS `{$schemaName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Set default schema
        $connection->exec("USE `{$schemaName}`");

        // Run migrations in schema
        if ($this->config['run_migrations']) {
            $this->runMigrations($tenant, $schemaName);
        }

        // Track provisioned schema
        $this->provisionedDatabases[$tenant->getId()] = [
            'strategy' => 'schema',
            'schema' => $schemaName,
            'connection' => $dbConfig['connection'] ?? 'default',
            'provisioned_at' => date('Y-m-d H:i:s'),
        ];

        return true;
    }

    /**
     * Provision with table prefix for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     */
    protected function provisionWithPrefix(TenantInterface $tenant, array $options): bool
    {
        $dbConfig = $tenant->getDatabaseConfig();
        $prefix = $dbConfig['prefix'] ?? $tenant->getId() . '_';

        // Run migrations with prefix
        if ($this->config['run_migrations']) {
            $this->runMigrationsWithPrefix($tenant, $prefix);
        }

        // Track provisioned prefix
        $this->provisionedDatabases[$tenant->getId()] = [
            'strategy' => 'prefix',
            'prefix' => $prefix,
            'connection' => $dbConfig['connection'] ?? 'default',
            'provisioned_at' => date('Y-m-d H:i:s'),
        ];

        return true;
    }

    /**
     * Create database user for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $databaseName Database name
     */
    protected function createDatabaseUser(TenantInterface $tenant, string $databaseName): void
    {
        $connection = $this->connectionManager->getConnection();
        $username = $tenant->getId() . '_user';
        $password = bin2hex(random_bytes(16));

        // Create user
        $connection->exec("CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY '{$password}'");
        
        // Grant permissions
        $connection->exec("GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$username}'@'%'");
        $connection->exec("FLUSH PRIVILEGES");
    }

    /**
     * Run migrations for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $target Database or schema name
     */
    protected function runMigrations(TenantInterface $tenant, string $target): void
    {
        // This would integrate with the Evolution system
        // For now, create basic tables
        $this->createBasicTables($target);
    }

    /**
     * Run migrations with prefix
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $prefix Table prefix
     */
    protected function runMigrationsWithPrefix(TenantInterface $tenant, string $prefix): void
    {
        // This would integrate with the Evolution system using prefixes
        // For now, create basic tables with prefix
        $this->createBasicTablesWithPrefix($prefix);
    }

    /**
     * Seed database with initial data
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $target Database or schema name
     */
    protected function seedDatabase(TenantInterface $tenant, string $target): void
    {
        // This would integrate with the Seeder system
        // For now, just a placeholder
    }

    /**
     * Create basic tables for tenant
     * 
     * @param string $target Database or schema name
     */
    protected function createBasicTables(string $target): void
    {
        $connection = $this->connectionManager->getConnection();
        
        // Switch to target database/schema
        $connection->exec("USE `{$target}`");
        
        // Create basic tables (example)
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(255) UNIQUE NOT NULL,
                value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];

        foreach ($tables as $sql) {
            $connection->exec($sql);
        }
    }

    /**
     * Create basic tables with prefix
     * 
     * @param string $prefix Table prefix
     */
    protected function createBasicTablesWithPrefix(string $prefix): void
    {
        $connection = $this->connectionManager->getConnection();
        
        // Create basic tables with prefix (example)
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$prefix}users (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS {$prefix}settings (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(255) UNIQUE NOT NULL,
                value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];

        foreach ($tables as $sql) {
            $connection->exec($sql);
        }
    }

    /**
     * Deprovision separate database
     * 
     * @param string $tenantId Tenant identifier
     * @param array $config Database configuration
     * @return bool Success status
     */
    protected function deprovisionSeparateDatabase(string $tenantId, array $config): bool
    {
        $connection = $this->connectionManager->getConnection();
        $databaseName = $config['database'];

        // Drop database
        $connection->exec("DROP DATABASE IF EXISTS `{$databaseName}`");

        // Drop user if exists
        if ($this->config['create_user']) {
            $username = $tenantId . '_user';
            $connection->exec("DROP USER IF EXISTS '{$username}'@'%'");
        }

        unset($this->provisionedDatabases[$tenantId]);
        return true;
    }

    /**
     * Deprovision schema
     * 
     * @param string $tenantId Tenant identifier
     * @param array $config Schema configuration
     * @return bool Success status
     */
    protected function deprovisionSchema(string $tenantId, array $config): bool
    {
        $connection = $this->connectionManager->getConnection();
        $schemaName = $config['schema'];

        // Drop schema
        $connection->exec("DROP SCHEMA IF EXISTS `{$schemaName}`");

        unset($this->provisionedDatabases[$tenantId]);
        return true;
    }

    /**
     * Deprovision tables with prefix
     * 
     * @param string $tenantId Tenant identifier
     * @param array $config Prefix configuration
     * @return bool Success status
     */
    protected function deprovisionWithPrefix(string $tenantId, array $config): bool
    {
        $connection = $this->connectionManager->getConnection();
        $prefix = $config['prefix'];

        // Get all tables with prefix
        $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Drop tables
        foreach ($tables as $table) {
            $connection->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        unset($this->provisionedDatabases[$tenantId]);
        return true;
    }
}