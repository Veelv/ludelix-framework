<?php

namespace Ludelix\Tenant\Commands;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Tenant List Command - List All Tenants
 * 
 * Mi command for listing tenants with filtering, sorting, and detailed information.
 * Supports various output formats and filtering options.
 * 
 * @package Ludelix\Tenant\Commands
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantListCommand extends BaseCommand
{
    /**
     * Command signature with options
     */
    protected string $signature = 'tenant:list [--status=] [--plan=] [--format=table] [--limit=50]';

    /**
     * Command description
     */
    protected string $description = 'List all tenants with filtering options';

    /**
     * Execute tenant list command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $this->info('Loading tenants...');

        try {
            // Get tenants with filters
            $tenants = $this->getTenants($options);
            
            if (empty($tenants)) {
                $this->info('No tenants found matching the criteria.');
                return 0;
            }

            // Display tenants based on format
            $format = $this->option($options, 'format', 'table');
            
            match($format) {
                'table' => $this->displayTable($tenants),
                'json' => $this->displayJson($tenants),
                'csv' => $this->displayCsv($tenants),
                default => $this->displayTable($tenants)
            };

            $this->line('');
            $this->info('Total tenants: ' . count($tenants));
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error('Failed to list tenants: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get tenants with applied filters
     * 
     * @param array $options Command options
     * @return array Filtered tenants
     */
    protected function getTenants(array $options): array
    {
        // This would integrate with tenant repository
        // For now, return sample data
        $sampleTenants = [
            [
                'id' => 'acme-corp',
                'name' => 'Acme Corporation',
                'status' => 'active',
                'plan' => 'enterprise',
                'domain' => 'acme.app.com',
                'created_at' => '2024-01-15 10:30:00',
                'users' => 150,
                'storage' => '2.5GB'
            ],
            [
                'id' => 'tech-startup',
                'name' => 'Tech Startup Inc',
                'status' => 'active',
                'plan' => 'professional',
                'domain' => 'techstartup.app.com',
                'created_at' => '2024-01-20 14:15:00',
                'users' => 25,
                'storage' => '500MB'
            ],
            [
                'id' => 'small-biz',
                'name' => 'Small Business',
                'status' => 'suspended',
                'plan' => 'basic',
                'domain' => 'smallbiz.app.com',
                'created_at' => '2024-01-10 09:00:00',
                'users' => 5,
                'storage' => '100MB'
            ]
        ];

        // Apply filters
        $filtered = $sampleTenants;
        
        if ($status = $this->option($options, 'status')) {
            $filtered = array_filter($filtered, fn($t) => $t['status'] === $status);
        }
        
        if ($plan = $this->option($options, 'plan')) {
            $filtered = array_filter($filtered, fn($t) => $t['plan'] === $plan);
        }
        
        // Apply limit
        $limit = (int) $this->option($options, 'limit', 50);
        if ($limit > 0) {
            $filtered = array_slice($filtered, 0, $limit);
        }

        return array_values($filtered);
    }

    /**
     * Display tenants in table format
     * 
     * @param array $tenants Tenant data
     */
    protected function displayTable(array $tenants): void
    {
        $headers = ['ID', 'Name', 'Status', 'Plan', 'Domain', 'Users', 'Storage', 'Created'];
        $rows = [];

        foreach ($tenants as $tenant) {
            $rows[] = [
                $tenant['id'],
                $tenant['name'],
                $this->formatStatus($tenant['status']),
                ucfirst($tenant['plan']),
                $tenant['domain'],
                $tenant['users'],
                $tenant['storage'],
                date('M j, Y', strtotime($tenant['created_at']))
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Display tenants in JSON format
     * 
     * @param array $tenants Tenant data
     */
    protected function displayJson(array $tenants): void
    {
        echo json_encode($tenants, JSON_PRETTY_PRINT) . "\n";
    }

    /**
     * Display tenants in CSV format
     * 
     * @param array $tenants Tenant data
     */
    protected function displayCsv(array $tenants): void
    {
        // CSV headers
        echo "ID,Name,Status,Plan,Domain,Users,Storage,Created\n";
        
        // CSV rows
        foreach ($tenants as $tenant) {
            echo implode(',', [
                $tenant['id'],
                '"' . $tenant['name'] . '"',
                $tenant['status'],
                $tenant['plan'],
                $tenant['domain'],
                $tenant['users'],
                $tenant['storage'],
                $tenant['created_at']
            ]) . "\n";
        }
    }

    /**
     * Format status with colors
     * 
     * @param string $status Tenant status
     * @return string Formatted status
     */
    protected function formatStatus(string $status): string
    {
        return match($status) {
            'active' => '🟢 Active',
            'suspended' => '🔴 Suspended',
            'inactive' => '🟡 Inactive',
            'maintenance' => '🔧 Maintenance',
            default => $status
        };
    }
}