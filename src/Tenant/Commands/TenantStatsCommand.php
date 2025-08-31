<?php

namespace Ludelix\Tenant\Commands;

use Ludelix\Core\Console\Commands\Core\BaseCommand;

/**
 * Tenant Stats Command - Display Tenant Statistics
 * 
 * Mi command for displaying comprehensive tenant statistics including
 * usage metrics, performance data, and resource utilization.
 * 
 * @package Ludelix\Tenant\Commands
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantStatsCommand extends BaseCommand
{
    /**
     * Command signature with arguments and options
     */
    protected string $signature = 'tenant:stats [tenant] [--period=30days] [--format=table] [--export=]';

    /**
     * Command description
     */
    protected string $description = 'Display tenant statistics and usage metrics';

    /**
     * Execute tenant stats command
     * 
     * @param array $arguments Command arguments
     * @param array $options Command options
     * @return int Exit code
     */
    public function execute(array $arguments, array $options): int
    {
        $tenantId = $this->argument($arguments, 0);
        $period = $this->option($options, 'period', '30days');
        $format = $this->option($options, 'format', 'table');

        try {
            if ($tenantId) {
                return $this->showTenantStats($tenantId, $period, $format, $options);
            } else {
                return $this->showGlobalStats($period, $format, $options);
            }
            
        } catch (\Throwable $e) {
            $this->error('Failed to retrieve statistics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show statistics for specific tenant
     * 
     * @param string $tenantId Tenant identifier
     * @param string $period Time period
     * @param string $format Output format
     * @param array $options Command options
     * @return int Exit code
     */
    protected function showTenantStats(string $tenantId, string $period, string $format, array $options): int
    {
        $this->info("Tenant Statistics: {$tenantId} (Last {$period})");
        $this->line('');

        // Get tenant statistics (sample data)
        $stats = $this->getTenantStatistics($tenantId, $period);

        if ($format === 'json') {
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            return 0;
        }

        // Display overview
        $this->displayTenantOverview($stats['overview']);
        
        // Display resource usage
        $this->displayResourceUsage($stats['resources']);
        
        // Display performance metrics
        $this->displayPerformanceMetrics($stats['performance']);
        
        // Display activity summary
        $this->displayActivitySummary($stats['activity']);

        // Export if requested
        if ($export = $this->option($options, 'export')) {
            $this->exportStats($stats, $export, $tenantId);
        }

        return 0;
    }

    /**
     * Show global statistics for all tenants
     * 
     * @param string $period Time period
     * @param string $format Output format
     * @param array $options Command options
     * @return int Exit code
     */
    protected function showGlobalStats(string $period, string $format, array $options): int
    {
        $this->info("Global Tenant Statistics (Last {$period})");
        $this->line('');

        // Get global statistics (sample data)
        $stats = $this->getGlobalStatistics($period);

        if ($format === 'json') {
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            return 0;
        }

        // Display global overview
        $this->displayGlobalOverview($stats);

        return 0;
    }

    /**
     * Get tenant-specific statistics
     * 
     * @param string $tenantId Tenant identifier
     * @param string $period Time period
     * @return array Statistics data
     */
    protected function getTenantStatistics(string $tenantId, string $period): array
    {
        // This would integrate with analytics system
        // For now, return sample data
        return [
            'overview' => [
                'tenant_id' => $tenantId,
                'name' => 'Acme Corporation',
                'status' => 'active',
                'plan' => 'enterprise',
                'created_at' => '2024-01-15 10:30:00',
            ],
            'resources' => [
                'storage' => ['used' => '2.5GB', 'quota' => '100GB', 'percentage' => 2.5],
                'users' => ['active' => 150, 'quota' => 1000, 'percentage' => 15],
                'api_calls' => ['used' => 45000, 'quota' => 1000000, 'percentage' => 4.5],
                'bandwidth' => ['used' => '15GB', 'quota' => '500GB', 'percentage' => 3],
            ],
            'performance' => [
                'avg_response_time' => '120ms',
                'uptime' => '99.9%',
                'error_rate' => '0.1%',
                'cache_hit_ratio' => '85%',
            ],
            'activity' => [
                'total_requests' => 125000,
                'unique_visitors' => 2500,
                'page_views' => 85000,
                'active_sessions' => 45,
            ]
        ];
    }

    /**
     * Get global statistics for all tenants
     * 
     * @param string $period Time period
     * @return array Global statistics
     */
    protected function getGlobalStatistics(string $period): array
    {
        return [
            'total_tenants' => 1250,
            'active_tenants' => 1180,
            'suspended_tenants' => 45,
            'inactive_tenants' => 25,
            'total_users' => 125000,
            'total_storage' => '2.5TB',
            'total_requests' => 15000000,
            'avg_response_time' => '95ms',
            'global_uptime' => '99.95%',
            'top_tenants' => [
                ['id' => 'acme-corp', 'requests' => 125000],
                ['id' => 'tech-startup', 'requests' => 98000],
                ['id' => 'enterprise-co', 'requests' => 87000],
            ]
        ];
    }

    /**
     * Display tenant overview information
     * 
     * @param array $overview Overview data
     */
    protected function displayTenantOverview(array $overview): void
    {
        $this->info('📊 Tenant Overview');
        $this->line("  Name: {$overview['name']}");
        $this->line("  Status: " . $this->formatStatus($overview['status']));
        $this->line("  Plan: " . ucfirst($overview['plan']));
        $this->line("  Created: " . date('M j, Y', strtotime($overview['created_at'])));
        $this->line('');
    }

    /**
     * Display resource usage information
     * 
     * @param array $resources Resource data
     */
    protected function displayResourceUsage(array $resources): void
    {
        $this->info('💾 Resource Usage');
        
        foreach ($resources as $resource => $data) {
            $bar = $this->createProgressBar($data['percentage']);
            $this->line("  " . ucfirst($resource) . ": {$data['used']} / {$data['quota']} {$bar} {$data['percentage']}%");
        }
        
        $this->line('');
    }

    /**
     * Display performance metrics
     * 
     * @param array $performance Performance data
     */
    protected function displayPerformanceMetrics(array $performance): void
    {
        $this->info('⚡ Performance Metrics');
        $this->line("  Average Response Time: {$performance['avg_response_time']}");
        $this->line("  Uptime: {$performance['uptime']}");
        $this->line("  Error Rate: {$performance['error_rate']}");
        $this->line("  Cache Hit Ratio: {$performance['cache_hit_ratio']}");
        $this->line('');
    }

    /**
     * Display activity summary
     * 
     * @param array $activity Activity data
     */
    protected function displayActivitySummary(array $activity): void
    {
        $this->info('📈 Activity Summary');
        $this->line("  Total Requests: " . number_format($activity['total_requests']));
        $this->line("  Unique Visitors: " . number_format($activity['unique_visitors']));
        $this->line("  Page Views: " . number_format($activity['page_views']));
        $this->line("  Active Sessions: {$activity['active_sessions']}");
        $this->line('');
    }

    /**
     * Display global overview
     * 
     * @param array $stats Global statistics
     */
    protected function displayGlobalOverview(array $stats): void
    {
        $this->info('🌐 Global Overview');
        $this->line("  Total Tenants: " . number_format($stats['total_tenants']));
        $this->line("  Active: " . number_format($stats['active_tenants']));
        $this->line("  Suspended: " . number_format($stats['suspended_tenants']));
        $this->line("  Inactive: " . number_format($stats['inactive_tenants']));
        $this->line('');
        
        $this->info('📊 System Metrics');
        $this->line("  Total Users: " . number_format($stats['total_users']));
        $this->line("  Total Storage: {$stats['total_storage']}");
        $this->line("  Total Requests: " . number_format($stats['total_requests']));
        $this->line("  Avg Response Time: {$stats['avg_response_time']}");
        $this->line("  Global Uptime: {$stats['global_uptime']}");
        $this->line('');
        
        $this->info('🏆 Top Tenants');
        foreach ($stats['top_tenants'] as $tenant) {
            $this->line("  {$tenant['id']}: " . number_format($tenant['requests']) . " requests");
        }
    }

    /**
     * Create progress bar visualization
     * 
     * @param float $percentage Percentage value
     * @return string Progress bar string
     */
    protected function createProgressBar(float $percentage): string
    {
        $width = 20;
        $filled = (int) ($width * ($percentage / 100));
        $empty = $width - $filled;
        
        return '[' . str_repeat('█', $filled) . str_repeat('░', $empty) . ']';
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

    /**
     * Export statistics to file
     * 
     * @param array $stats Statistics data
     * @param string $format Export format
     * @param string $tenantId Tenant identifier
     */
    protected function exportStats(array $stats, string $format, string $tenantId): void
    {
        $filename = "tenant_stats_{$tenantId}_" . date('Y-m-d_H-i-s') . ".{$format}";
        
        match($format) {
            'json' => file_put_contents($filename, json_encode($stats, JSON_PRETTY_PRINT)),
            'csv' => $this->exportToCsv($stats, $filename),
            default => $this->info("Unsupported export format: {$format}")
        };
        
        if (file_exists($filename)) {
            $this->success("Statistics exported to: {$filename}");
        }
    }

    /**
     * Export statistics to CSV format
     * 
     * @param array $stats Statistics data
     * @param string $filename Output filename
     */
    protected function exportToCsv(array $stats, string $filename): void
    {
        $csv = fopen($filename, 'w');
        
        // Write headers and data for each section
        foreach ($stats as $section => $data) {
            fputcsv($csv, [$section]);
            
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    fputcsv($csv, [$key, is_array($value) ? json_encode($value) : $value]);
                }
            }
            
            fputcsv($csv, []); // Empty row
        }
        
        fclose($csv);
    }
}