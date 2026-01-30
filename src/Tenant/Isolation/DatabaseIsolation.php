<?php

namespace Ludelix\Tenant\Isolation;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Database\Core\ConnectionManager;

/**
 * Database Isolation - Tenant Database Isolation Manager
 * 
 * Manages database isolation strategies for multi-tenant applications.
 * Supports multiple isolation approaches including separate databases,
 * schema prefixing, and row-level isolation.
 * 
 * @package Ludelix\Tenant\Isolation
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class DatabaseIsolation
{
    /**
     * Database connection manager
     */
    protected ConnectionManager $connectionManager;

    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Active isolation strategy
     */
    protected string $strategy = 'prefix';

    /**
     * Connection cache for performance
     */
    protected array $connectionCache = [];

    /**
     * Initialize database isolation manager
     * 
     * @param ConnectionManager $connectionManager Database connection manager
     * @param array $config Isolation configuration
     */
    public function __construct(ConnectionManager $connectionManager, array $config = [])
    {
        $this->connectionManager = $connectionManager;
        $this->strategy = $config['default_strategy'] ?? 'prefix';
    }

    /**
     * Switch database context to specific tenant
     * 
     * @param TenantInterface $tenant Target tenant
     * @return self Fluent interface
     */
    public function switchTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        
        $dbConfig = $tenant->getDatabaseConfig();
        $strategy = $dbConfig['strategy'] ?? $this->strategy;
        
        switch ($strategy) {
            case 'separate':
                $this->applySeparateDatabaseStrategy($tenant);
                break;
            case 'schema':
                $this->applySchemaStrategy($tenant);
                break;
            case 'prefix':
                $this->applyPrefixStrategy($tenant);
                break;
            case 'row_level':
                $this->applyRowLevelStrategy($tenant);
                break;
            default:
                throw new \InvalidArgumentException("Unknown isolation strategy: {$strategy}");
        }

        return $this;
    }

    /**
     * Get tenant-aware database connection
     * 
     * @param string|null $name Connection name
     * @return \PDO Database connection
     */
    public function getConnection(?string $name = null): \PDO
    {
        if (!$this->currentTenant) {
            return $this->connectionManager->getConnection($name);
        }

        $tenantId = $this->currentTenant->getId();
        $cacheKey = $tenantId . ':' . ($name ?? 'default');
        
        if (isset($this->connectionCache[$cacheKey])) {
            return $this->connectionCache[$cacheKey];
        }

        $connection = $this->createTenantConnection($name);
        $this->connectionCache[$cacheKey] = $connection;
        
        return $connection;
    }

    /**
     * Get current tenant context
     * 
     * @return TenantInterface|null Current tenant
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    /**
     * Get table name with tenant-specific prefix
     * 
     * @param string $tableName Base table name
     * @return string Prefixed table name
     */
    public function getTableName(string $tableName): string
    {
        if (!$this->currentTenant) {
            return $tableName;
        }

        $dbConfig = $this->currentTenant->getDatabaseConfig();
        $prefix = $dbConfig['prefix'] ?? '';
        
        return $prefix . $tableName;
    }

    /**
     * Clear connection cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->connectionCache = [];
        return $this;
    }

    /**
     * Apply separate database isolation strategy
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function applySeparateDatabaseStrategy(TenantInterface $tenant): void
    {
        $dbConfig = $tenant->getDatabaseConfig();
        $tenantDatabase = $dbConfig['database'] ?? $tenant->getId() . '_db';
        
        // This would configure connection manager to use tenant-specific database
        // Implementation depends on specific database setup
    }

    /**
     * Apply schema-based isolation strategy
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function applySchemaStrategy(TenantInterface $tenant): void
    {
        $dbConfig = $tenant->getDatabaseConfig();
        $schema = $dbConfig['schema'] ?? $tenant->getId();
        
        // Set default schema for tenant
        $connection = $this->connectionManager->getConnection();
        $connection->exec("USE `{$schema}`");
    }

    /**
     * Apply table prefix isolation strategy
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function applyPrefixStrategy(TenantInterface $tenant): void
    {
        // Prefix strategy is handled in getTableName() method
        // No immediate database changes needed
    }

    /**
     * Apply row-level isolation strategy
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function applyRowLevelStrategy(TenantInterface $tenant): void
    {
        // Row-level strategy requires application-level filtering
        // This would typically set a global tenant_id filter
    }

    /**
     * Create tenant-specific database connection
     * 
     * @param string|null $name Connection name
     * @return \PDO Database connection
     */
    protected function createTenantConnection(?string $name): \PDO
    {
        $dbConfig = $this->currentTenant->getDatabaseConfig();
        $strategy = $dbConfig['strategy'] ?? $this->strategy;
        
        switch ($strategy) {
            case 'separate':
                return $this->createSeparateConnection($name);
            default:
                return $this->connectionManager->getConnection($name);
        }
    }

    /**
     * Create separate database connection for tenant
     * 
     * @param string|null $name Connection name
     * @return \PDO Database connection
     */
    protected function createSeparateConnection(?string $name): \PDO
    {
        // This would create a connection to tenant-specific database
        // For now, return default connection
        return $this->connectionManager->getConnection($name);
    }
}