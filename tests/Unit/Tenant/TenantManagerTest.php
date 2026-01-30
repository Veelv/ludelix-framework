<?php

namespace Ludelix\Tests\Unit\Tenant;

use PHPUnit\Framework\TestCase;
use Ludelix\Tenant\Core\TenantManager;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\Tenant\Exceptions\TenantProvisioningException;

class TenantManagerTest extends TestCase
{
    public function testTenantManagerSingletonThrowsExceptionWithoutDependencies(): void
    {
        $this->expectException(TenantProvisioningException::class);
        $this->expectExceptionMessage('TenantManager instance not initialized');

        TenantManager::instance();
    }

    public function testTenantCreationAndBasicOperations(): void
    {
        $tenant = new Tenant([
            'id' => 'manager-test',
            'name' => 'Manager Test Tenant'
        ]);

        $this->assertEquals('manager-test', $tenant->getId());
        $this->assertEquals('Manager Test Tenant', $tenant->getName());
        $this->assertTrue($tenant->isActive());
    }

    public function testTenantWithHierarchy(): void
    {
        $parentTenant = new Tenant([
            'id' => 'parent-tenant',
            'name' => 'Parent Tenant',
            'children_ids' => ['child-tenant-1', 'child-tenant-2']
        ]);

        $childTenant = new Tenant([
            'id' => 'child-tenant-1',
            'name' => 'Child Tenant 1',
            'parent_id' => 'parent-tenant'
        ]);

        $this->assertNull($parentTenant->getParentId());
        $this->assertEquals(['child-tenant-1', 'child-tenant-2'], $parentTenant->getChildrenIds());
        
        $this->assertEquals('parent-tenant', $childTenant->getParentId());
        $this->assertEmpty($childTenant->getChildrenIds());
    }

    public function testTenantDatabaseConfiguration(): void
    {
        $tenant = new Tenant([
            'id' => 'db-test',
            'database' => [
                'strategy' => 'dedicated',
                'connection' => 'tenant_db',
                'prefix' => 'custom_',
                'encryption' => true
            ]
        ]);

        $dbConfig = $tenant->getDatabaseConfig();
        
        $this->assertEquals('dedicated', $dbConfig['strategy']);
        $this->assertEquals('tenant_db', $dbConfig['connection']);
        $this->assertEquals('custom_', $dbConfig['prefix']);
        $this->assertTrue($dbConfig['encryption']);
    }

    public function testTenantCacheConfiguration(): void
    {
        $tenant = new Tenant([
            'id' => 'cache-test',
            'cache' => [
                'prefix' => 'custom:cache:',
                'ttl_multiplier' => 2.0,
                'driver' => 'redis'
            ]
        ]);

        $cacheConfig = $tenant->getCacheConfig();
        
        $this->assertEquals('custom:cache:', $cacheConfig['prefix']);
        $this->assertEquals(2.0, $cacheConfig['ttl_multiplier']);
        $this->assertEquals('redis', $cacheConfig['driver']);
    }

    public function testTenantDomainConfiguration(): void
    {
        $tenant = new Tenant([
            'id' => 'domain-test',
            'domain' => [
                'primary' => 'acme.example.com',
                'aliases' => ['acme-corp.com', 'acme.co'],
                'subdomain' => 'acme',
                'custom_domains' => ['custom.acme.com']
            ]
        ]);

        $domainConfig = $tenant->getDomain();
        
        $this->assertEquals('acme.example.com', $domainConfig['primary']);
        $this->assertEquals(['acme-corp.com', 'acme.co'], $domainConfig['aliases']);
        $this->assertEquals('acme', $domainConfig['subdomain']);
        $this->assertEquals(['custom.acme.com'], $domainConfig['custom_domains']);
    }
}