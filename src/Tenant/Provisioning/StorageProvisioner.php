<?php

namespace Ludelix\Tenant\Provisioning;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Storage Provisioner - Tenant Storage Setup Manager
 * 
 * Handles storage provisioning for tenants including directory creation,
 * permission setup, quota allocation, and storage isolation configuration.
 * 
 * @package Ludelix\Tenant\Provisioning
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class StorageProvisioner
{
    /**
     * Storage configuration
     */
    protected array $config;

    /**
     * Provisioned storage tracking
     */
    protected array $provisionedStorage = [];

    /**
     * Base storage path
     */
    protected string $basePath;

    /**
     * Initialize storage provisioner
     * 
     * @param array $config Storage provisioning configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'base_path' => 'storage',
            'tenant_dir' => 'tenants',
            'create_subdirs' => true,
            'set_permissions' => true,
            'default_quota' => '1GB',
        ], $config);
        
        $this->basePath = $this->config['base_path'];
    }

    /**
     * Provision storage resources for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     * @throws \Exception If provisioning fails
     */
    public function provision(TenantInterface $tenant, array $options = []): bool
    {
        try {
            $tenantPath = $this->buildTenantPath($tenant);
            
            // Create tenant directory structure
            $this->createTenantDirectories($tenant, $tenantPath);
            
            // Set directory permissions
            if ($this->config['set_permissions']) {
                $this->setDirectoryPermissions($tenantPath);
            }
            
            // Create storage configuration file
            $this->createStorageConfig($tenant, $tenantPath);
            
            // Set up quota monitoring
            $this->setupQuotaMonitoring($tenant, $tenantPath);
            
            // Track provisioned storage
            $this->provisionedStorage[$tenant->getId()] = [
                'path' => $tenantPath,
                'quota' => $this->getTenantQuota($tenant),
                'provisioned_at' => date('Y-m-d H:i:s'),
                'directories' => $this->getCreatedDirectories($tenantPath),
            ];
            
            return true;
            
        } catch (\Throwable $e) {
            throw new \Exception("Storage provisioning failed for tenant {$tenant->getId()}: " . $e->getMessage());
        }
    }

    /**
     * Deprovision storage resources for tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function deprovision(string $tenantId): bool
    {
        if (!isset($this->provisionedStorage[$tenantId])) {
            return true; // Already deprovisioned
        }

        try {
            $config = $this->provisionedStorage[$tenantId];
            $tenantPath = $config['path'];
            
            // Backup tenant data if needed
            $this->backupTenantStorage($tenantId, $tenantPath);
            
            // Remove tenant directory
            $this->removeTenantDirectory($tenantPath);
            
            // Clean up quota monitoring
            $this->cleanupQuotaMonitoring($tenantId);
            
            unset($this->provisionedStorage[$tenantId]);
            
            return true;
            
        } catch (\Throwable $e) {
            error_log("Storage deprovisioning failed for tenant {$tenantId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get storage provisioning status
     * 
     * @param string $tenantId Tenant identifier
     * @return array Status information
     */
    public function getStatus(string $tenantId): array
    {
        if (!isset($this->provisionedStorage[$tenantId])) {
            return [
                'status' => 'not_provisioned',
                'message' => 'Storage not provisioned',
                'path' => null,
            ];
        }

        $config = $this->provisionedStorage[$tenantId];
        $usage = $this->calculateStorageUsage($config['path']);
        
        return [
            'status' => 'ready',
            'message' => 'Storage provisioned successfully',
            'path' => $config['path'],
            'quota' => $config['quota'],
            'usage' => $usage,
            'provisioned_at' => $config['provisioned_at'],
            'directories' => $config['directories'],
        ];
    }

    /**
     * Rollback storage provisioning
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function rollback(string $tenantId): bool
    {
        return $this->deprovision($tenantId);
    }

    /**
     * Build tenant storage path
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return string Tenant storage path
     */
    protected function buildTenantPath(TenantInterface $tenant): string
    {
        return $this->basePath . '/' . $this->config['tenant_dir'] . '/' . $tenant->getId();
    }

    /**
     * Create tenant directory structure
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $tenantPath Base tenant path
     */
    protected function createTenantDirectories(TenantInterface $tenant, string $tenantPath): void
    {
        $directories = [
            $tenantPath,
            $tenantPath . '/uploads',
            $tenantPath . '/cache',
            $tenantPath . '/temp',
            $tenantPath . '/logs',
            $tenantPath . '/backups',
            $tenantPath . '/exports',
            $tenantPath . '/imports',
        ];

        // Add custom directories if configured
        if ($this->config['create_subdirs']) {
            $customDirs = $tenant->getConfig('storage.directories', []);
            foreach ($customDirs as $dir) {
                $directories[] = $tenantPath . '/' . $dir;
            }
        }

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new \Exception("Failed to create directory: {$dir}");
                }
            }
        }
    }

    /**
     * Set directory permissions
     * 
     * @param string $tenantPath Tenant path
     */
    protected function setDirectoryPermissions(string $tenantPath): void
    {
        $permissions = [
            $tenantPath => 0755,
            $tenantPath . '/uploads' => 0755,
            $tenantPath . '/cache' => 0755,
            $tenantPath . '/temp' => 0777,
            $tenantPath . '/logs' => 0755,
            $tenantPath . '/backups' => 0700,
        ];

        foreach ($permissions as $path => $permission) {
            if (is_dir($path)) {
                chmod($path, $permission);
            }
        }
    }

    /**
     * Create storage configuration file
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $tenantPath Tenant path
     */
    protected function createStorageConfig(TenantInterface $tenant, string $tenantPath): void
    {
        $config = [
            'tenant_id' => $tenant->getId(),
            'tenant_name' => $tenant->getName(),
            'quota' => $this->getTenantQuota($tenant),
            'created_at' => date('Y-m-d H:i:s'),
            'directories' => $this->getCreatedDirectories($tenantPath),
        ];

        $configFile = $tenantPath . '/.storage_config.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Setup quota monitoring for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $tenantPath Tenant path
     */
    protected function setupQuotaMonitoring(TenantInterface $tenant, string $tenantPath): void
    {
        // Create quota monitoring file
        $quotaFile = $tenantPath . '/.quota';
        $quota = $this->getTenantQuota($tenant);
        
        file_put_contents($quotaFile, json_encode([
            'quota' => $quota,
            'quota_bytes' => $this->parseQuotaToBytes($quota),
            'monitoring_enabled' => true,
            'last_check' => date('Y-m-d H:i:s'),
        ]));
    }

    /**
     * Get tenant storage quota
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return string Storage quota
     */
    protected function getTenantQuota(TenantInterface $tenant): string
    {
        $quotas = $tenant->getResourceQuotas();
        return $quotas['quotas']['storage'] ?? $this->config['default_quota'];
    }

    /**
     * Get created directories list
     * 
     * @param string $tenantPath Tenant path
     * @return array Directory list
     */
    protected function getCreatedDirectories(string $tenantPath): array
    {
        if (!is_dir($tenantPath)) {
            return [];
        }

        $directories = [];
        $iterator = new \DirectoryIterator($tenantPath);
        
        foreach ($iterator as $item) {
            if ($item->isDot()) continue;
            if ($item->isDir()) {
                $directories[] = $item->getFilename();
            }
        }
        
        return $directories;
    }

    /**
     * Calculate storage usage for tenant
     * 
     * @param string $tenantPath Tenant path
     * @return array Usage information
     */
    protected function calculateStorageUsage(string $tenantPath): array
    {
        if (!is_dir($tenantPath)) {
            return ['used' => 0, 'files' => 0, 'directories' => 0];
        }

        $totalSize = 0;
        $fileCount = 0;
        $dirCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tenantPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
                $fileCount++;
            } elseif ($file->isDir()) {
                $dirCount++;
            }
        }

        return [
            'used' => $totalSize,
            'used_formatted' => $this->formatBytes($totalSize),
            'files' => $fileCount,
            'directories' => $dirCount,
        ];
    }

    /**
     * Backup tenant storage before deprovisioning
     * 
     * @param string $tenantId Tenant identifier
     * @param string $tenantPath Tenant path
     */
    protected function backupTenantStorage(string $tenantId, string $tenantPath): void
    {
        $backupPath = $this->basePath . '/backups/tenant_' . $tenantId . '_' . date('Y-m-d_H-i-s');
        
        if (is_dir($tenantPath)) {
            // Create backup directory
            mkdir($backupPath, 0755, true);
            
            // This would implement actual backup logic
            // For now, just create a backup marker
            file_put_contents($backupPath . '/backup_info.json', json_encode([
                'tenant_id' => $tenantId,
                'original_path' => $tenantPath,
                'backup_created' => date('Y-m-d H:i:s'),
            ]));
        }
    }

    /**
     * Remove tenant directory recursively
     * 
     * @param string $tenantPath Tenant path
     */
    protected function removeTenantDirectory(string $tenantPath): void
    {
        if (!is_dir($tenantPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tenantPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($tenantPath);
    }

    /**
     * Cleanup quota monitoring
     * 
     * @param string $tenantId Tenant identifier
     */
    protected function cleanupQuotaMonitoring(string $tenantId): void
    {
        // Remove quota monitoring files/records
        // This would integrate with monitoring system
    }

    /**
     * Parse quota string to bytes
     * 
     * @param string $quota Quota string
     * @return int Bytes
     */
    protected function parseQuotaToBytes(string $quota): int
    {
        if (is_numeric($quota)) {
            return (int) $quota;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(GB|MB|KB|TB)$/i', $quota, $matches)) {
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            
            return (int) match($unit) {
                'KB' => $number * 1024,
                'MB' => $number * 1024 * 1024,
                'GB' => $number * 1024 * 1024 * 1024,
                'TB' => $number * 1024 * 1024 * 1024 * 1024,
                default => $number
            };
        }

        return 0;
    }

    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}