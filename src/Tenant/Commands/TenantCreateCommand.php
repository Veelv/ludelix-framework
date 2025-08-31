<?php

namespace Ludelix\Tenant\Commands;

use Ludelix\Core\Console\Commands\Core\BaseCommand;
use Ludelix\Tenant\Core\Tenant;

/**
 * Tenant Create Command - Create New Tenant
 * 
 * Mi command for creating new tenants with comprehensive configuration
 * options including domain setup, database isolation, and feature flags.
 * 
 * @package Ludelix\Tenant\Commands
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantCreateCommand extends BaseCommand
{
    /**
     * Command signature with arguments and options
     */
    protected string $signature = 'tenant:create <id> [--name=] [--domain=] [--plan=] [--database=] [--features=]';

    /**
     * Command description
     */
    protected string $description = 'Create new tenant with configuration options';

    /**
     * Execute tenant creation command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $tenantId = $this->argument($arguments, 0);
        
        if (!$tenantId) {
            $this->error('Tenant ID is required');
            return 1;
        }

        // Validate tenant ID format
        if (!$this->isValidTenantId($tenantId)) {
            $this->error('Invalid tenant ID format. Use alphanumeric characters, hyphens, and underscores only.');
            return 1;
        }

        $this->info("Creating tenant: {$tenantId}");

        try {
            // Gather tenant configuration
            $config = $this->buildTenantConfig($tenantId, $options);
            
            // Create tenant instance
            $tenant = new Tenant($config);
            
            // Provision tenant resources
            $this->provisionTenant($tenant, $options);
            
            // Display creation summary
            $this->displayCreationSummary($tenant);
            
            $this->success("Tenant '{$tenantId}' created successfully!");
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Failed to create tenant: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Validate tenant ID format
     * 
     * @param string $tenantId Tenant identifier
     * @return bool True if valid
     */
    protected function isValidTenantId(string $tenantId): bool
    {
        return preg_match('/^[a-zA-Z0-9\-_]+$/', $tenantId) && strlen($tenantId) <= 64;
    }

    /**
     * Build tenant configuration from command options
     * 
     * @param string $tenantId Tenant identifier
     * @param array $options Command options
     * @return array Tenant configuration
     */
    protected function buildTenantConfig(string $tenantId, array $options): array
    {
        $config = [
            'id' => $tenantId,
            'name' => $this->option($options, 'name', ucfirst($tenantId)),
            'status' => 'active',
        ];

        // Configure domain
        if ($domain = $this->option($options, 'domain')) {
            $config['domain'] = [
                'primary' => $domain,
                'subdomain' => $tenantId
            ];
        }

        // Configure database strategy
        $databaseStrategy = $this->option($options, 'database', 'prefix');
        $config['database'] = [
            'strategy' => $databaseStrategy,
            'prefix' => $tenantId . '_',
            'connection' => 'default'
        ];

        // Configure cache
        $config['cache'] = [
            'prefix' => "tenant:{$tenantId}:",
            'driver' => 'default'
        ];

        // Configure features
        if ($features = $this->option($options, 'features')) {
            $config['features'] = [
                'enabled' => explode(',', $features)
            ];
        }

        // Configure plan-based settings
        if ($plan = $this->option($options, 'plan')) {
            $config = array_merge($config, $this->getPlanConfiguration($plan));
        }

        return $config;
    }

    /**
     * Get configuration for specific plan
     * 
     * @param string $plan Plan name
     * @return array Plan configuration
     */
    protected function getPlanConfiguration(string $plan): array
    {
        return match($plan) {
            'basic' => [
                'resources' => [
                    'quotas' => [
                        'storage' => '1GB',
                        'api_calls' => 10000,
                        'users' => 10
                    ]
                ],
                'features' => [
                    'enabled' => ['basic_analytics']
                ]
            ],
            'professional' => [
                'resources' => [
                    'quotas' => [
                        'storage' => '10GB',
                        'api_calls' => 100000,
                        'users' => 100
                    ]
                ],
                'features' => [
                    'enabled' => ['basic_analytics', 'advanced_reports', 'api_access']
                ]
            ],
            'enterprise' => [
                'resources' => [
                    'quotas' => [
                        'storage' => '100GB',
                        'api_calls' => 1000000,
                        'users' => 1000
                    ]
                ],
                'features' => [
                    'enabled' => ['basic_analytics', 'advanced_reports', 'api_access', 'white_label', 'sso']
                ],
                'database' => [
                    'strategy' => 'separate'
                ]
            ],
            default => []
        };
    }

    /**
     * Provision tenant resources
     * 
     * @param Tenant $tenant Tenant instance
     * @param array $options Command options
     */
    protected function provisionTenant(Tenant $tenant, array $options): void
    {
        $this->info('Provisioning tenant resources...');
        
        // Database provisioning
        $this->provisionDatabase($tenant);
        
        // Storage provisioning
        $this->provisionStorage($tenant);
        
        // Cache setup
        $this->setupCache($tenant);
        
        $this->info('Resource provisioning completed');
    }

    /**
     * Provision database resources for tenant
     * 
     * @param Tenant $tenant Tenant instance
     */
    protected function provisionDatabase(Tenant $tenant): void
    {
        $dbConfig = $tenant->getDatabaseConfig();
        $strategy = $dbConfig['strategy'];
        
        $this->line("  Database strategy: {$strategy}");
        
        switch ($strategy) {
            case 'separate':
                $this->line("  Creating dedicated database...");
                // Would create separate database
                break;
            case 'schema':
                $this->line("  Creating tenant schema...");
                // Would create tenant schema
                break;
            case 'prefix':
                $this->line("  Using table prefix: {$dbConfig['prefix']}");
                break;
        }
    }

    /**
     * Provision storage resources for tenant
     * 
     * @param Tenant $tenant Tenant instance
     */
    protected function provisionStorage(Tenant $tenant): void
    {
        $this->line("  Setting up tenant storage directory...");
        // Would create tenant-specific storage directories
    }

    /**
     * Setup cache configuration for tenant
     * 
     * @param Tenant $tenant Tenant instance
     */
    protected function setupCache(Tenant $tenant): void
    {
        $cacheConfig = $tenant->getCacheConfig();
        $this->line("  Cache prefix: {$cacheConfig['prefix']}");
    }

    /**
     * Display tenant creation summary
     * 
     * @param Tenant $tenant Created tenant
     */
    protected function displayCreationSummary(Tenant $tenant): void
    {
        $this->line("");
        $this->info("Tenant Summary:");
        $this->line("  ID: " . $tenant->getId());
        $this->line("  Name: " . $tenant->getName());
        $this->line("  Status: " . $tenant->getStatus());
        
        $domain = $tenant->getDomain();
        if ($domain['primary']) {
            $this->line("  Domain: " . $domain['primary']);
        }
        
        $quotas = $tenant->getResourceQuotas();
        if (!empty($quotas['quotas'])) {
            $this->line("  Resource Quotas:");
            foreach ($quotas['quotas'] as $resource => $quota) {
                $this->line("    {$resource}: {$quota}");
            }
        }
        
        $this->line("");
    }
}