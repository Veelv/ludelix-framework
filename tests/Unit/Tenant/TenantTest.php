<?php

namespace Ludelix\Tests\Unit\Tenant;

use PHPUnit\Framework\TestCase;
use Ludelix\Tenant\Core\Tenant;
use Ludelix\Tenant\Exceptions\TenantValidationException;

class TenantTest extends TestCase
{
    public function testTenantCreation(): void
    {
        $tenant = new Tenant([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'status' => 'active'
        ]);

        $this->assertEquals('test-tenant', $tenant->getId());
        $this->assertEquals('Test Tenant', $tenant->getName());
        $this->assertEquals('active', $tenant->getStatus());
        $this->assertTrue($tenant->isActive());
    }

    public function testTenantWithoutIdThrowsException(): void
    {
        $this->expectException(TenantValidationException::class);
        $this->expectExceptionMessage('Tenant ID is required');

        new Tenant([]);
    }

    public function testTenantFeatureManagement(): void
    {
        $tenant = new Tenant([
            'id' => 'feature-test',
            'features' => ['enabled' => ['analytics', 'api_access']]
        ]);

        $this->assertTrue($tenant->hasFeature('analytics'));
        $this->assertTrue($tenant->hasFeature('api_access'));
        $this->assertFalse($tenant->hasFeature('premium_support'));

        $tenant->enableFeature('premium_support');
        $this->assertTrue($tenant->hasFeature('premium_support'));

        $tenant->disableFeature('analytics');
        $this->assertFalse($tenant->hasFeature('analytics'));
    }

    public function testTenantConfiguration(): void
    {
        $tenant = new Tenant([
            'id' => 'config-test',
            'config' => [
                'app' => [
                    'name' => 'Custom App',
                    'theme' => 'dark'
                ]
            ]
        ]);

        $this->assertEquals('Custom App', $tenant->getConfig('app.name'));
        $this->assertEquals('dark', $tenant->getConfig('app.theme'));
        $this->assertEquals('default', $tenant->getConfig('app.missing', 'default'));

        $tenant->setConfig('app.locale', 'pt_BR');
        $this->assertEquals('pt_BR', $tenant->getConfig('app.locale'));
    }

    public function testResourceQuotas(): void
    {
        $tenant = new Tenant([
            'id' => 'quota-test',
            'resources' => [
                'quotas' => [
                    'storage' => '100GB',
                    'users' => 50
                ],
                'usage' => [
                    'storage' => '75GB',
                    'users' => 30
                ]
            ]
        ]);

        $quotas = $tenant->getResourceQuotas();
        
        $this->assertEquals('100GB', $quotas['quotas']['storage']);
        $this->assertEquals(50, $quotas['quotas']['users']);
        $this->assertEquals('75GB', $quotas['usage']['storage']);
        $this->assertEquals(30, $quotas['usage']['users']);

        $this->assertFalse($tenant->isResourceQuotaExceeded('storage'));
        $this->assertFalse($tenant->isResourceQuotaExceeded('users'));

        $tenant->updateResourceUsage('users', 60);
        $this->assertTrue($tenant->isResourceQuotaExceeded('users'));
    }

    public function testTenantSerialization(): void
    {
        $tenant = new Tenant([
            'id' => 'serialize-test',
            'name' => 'Serialization Test',
            'status' => 'active'
        ]);

        $array = $tenant->toArray();
        $this->assertIsArray($array);
        $this->assertEquals('serialize-test', $array['id']);
        $this->assertEquals('Serialization Test', $array['name']);

        $json = $tenant->toJson();
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('serialize-test', $decoded['id']);
    }
}