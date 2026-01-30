<?php

namespace Ludelix\Database\Migrations;

use Ludelix\Database\Core\ConnectionManager;

/**
 * Manages database migrations for multi-tenant applications.
 *
 * Facilitates executing migrations across multiple tenant databases
 * by switching connections dynamically.
 */
class TenantMigrationManager
{
    protected ConnectionManager $connectionManager;
    protected array $tenants = [];

    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * runs migrations for a specific tenant.
     *
     * Switches the active connection to the tenant's database and executes
     * pending migrations.
     *
     * @param string $tenantId The identifier of the tenant.
     */
    public function migrateForTenant(string $tenantId): void
    {
        // Switch to tenant database
        $connection = $this->connectionManager->getConnection($tenantId);

        // Run migrations for this tenant
        $this->runMigrations($connection);
    }

    /**
     * Runs migrations for all registered tenants.
     */
    public function migrateAllTenants(): void
    {
        foreach ($this->tenants as $tenantId) {
            $this->migrateForTenant($tenantId);
        }
    }

    /**
     * Executes the migration logic on the provided connection.
     *
     * @param mixed $connection The database connection.
     */
    protected function runMigrations(mixed $connection): void
    {
        // Migration logic here
        // This would typically involve checking a migrations table and running new migration files.
    }

    /**
     * Registers a tenant to be managed.
     *
     * @param string $tenantId
     */
    public function addTenant(string $tenantId): void
    {
        $this->tenants[] = $tenantId;
    }
}