<?php

namespace Ludelix\Database\Migrations;

use Ludelix\Database\Core\ConnectionManager;

class TenantMigrationManager
{
    protected ConnectionManager $connectionManager;
    protected array $tenants = [];
    
    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }
    
    public function migrateForTenant(string $tenantId): void
    {
        // Switch to tenant database
        $connection = $this->connectionManager->getConnection($tenantId);
        
        // Run migrations for this tenant
        $this->runMigrations($connection);
    }
    
    public function migrateAllTenants(): void
    {
        foreach ($this->tenants as $tenantId) {
            $this->migrateForTenant($tenantId);
        }
    }
    
    protected function runMigrations($connection): void
    {
        // Migration logic here
    }
    
    public function addTenant(string $tenantId): void
    {
        $this->tenants[] = $tenantId;
    }
}