<?php

namespace Ludelix\Tenant\Isolation;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Storage Isolation - Tenant File Storage Isolation Manager
 * 
 * Manages file storage isolation for multi-tenant applications using
 * tenant-specific directories, access controls, and storage quotas.
 * 
 * @package Ludelix\Tenant\Isolation
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class StorageIsolation
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Storage configuration
     */
    protected array $config;

    /**
     * Base storage path
     */
    protected string $basePath;

    /**
     * Current tenant storage path
     */
    protected string $tenantPath = '';

    /**
     * Initialize storage isolation manager
     * 
     * @param array $config Storage isolation configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'base_path' => 'cubby',
            'tenant_dir' => 'tenants',
            'shared_dirs' => ['temp', 'logs'],
            'create_dirs' => true,
        ], $config);
        
        $this->basePath = $this->config['base_path'];
    }

    /**
     * Switch storage context to specific tenant
     * 
     * @param TenantInterface $tenant Target tenant
     * @return self Fluent interface
     */
    public function switchTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        $this->tenantPath = $this->buildTenantPath($tenant);
        
        if ($this->config['create_dirs']) {
            $this->ensureTenantDirectories();
        }
        
        return $this;
    }

    /**
     * Get tenant-specific file path
     * 
     * @param string $path Relative file path
     * @return string Absolute tenant-specific path
     */
    public function getPath(string $path): string
    {
        // Handle shared directories
        foreach ($this->config['shared_dirs'] as $sharedDir) {
            if (str_starts_with($path, $sharedDir . '/')) {
                return $this->basePath . '/' . $path;
            }
        }

        // Apply tenant isolation
        if ($this->currentTenant && $this->tenantPath) {
            return $this->tenantPath . '/' . ltrim($path, '/');
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    /**
     * Get tenant storage directory
     * 
     * @return string Tenant storage directory
     */
    public function getTenantDirectory(): string
    {
        return $this->tenantPath;
    }

    /**
     * Check if file exists in tenant storage
     * 
     * @param string $path File path
     * @return bool True if file exists
     */
    public function exists(string $path): bool
    {
        return file_exists($this->getPath($path));
    }

    /**
     * Get file size with quota checking
     * 
     * @param string $path File path
     * @return int File size in bytes
     */
    public function size(string $path): int
    {
        $fullPath = $this->getPath($path);
        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }

    /**
     * Get tenant storage usage
     * 
     * @return array Storage usage information
     */
    public function getUsage(): array
    {
        if (!$this->currentTenant || !$this->tenantPath) {
            return ['used' => 0, 'quota' => 0, 'available' => 0];
        }

        $used = $this->calculateDirectorySize($this->tenantPath);
        $quotas = $this->currentTenant->getResourceQuotas();
        $quota = $this->parseStorageQuota($quotas['quotas']['storage'] ?? '0');
        
        return [
            'used' => $used,
            'quota' => $quota,
            'available' => max(0, $quota - $used),
            'percentage' => $quota > 0 ? ($used / $quota) * 100 : 0,
        ];
    }

    /**
     * Check if storage quota would be exceeded
     * 
     * @param int $additionalSize Additional size in bytes
     * @return bool True if quota would be exceeded
     */
    public function wouldExceedQuota(int $additionalSize): bool
    {
        $usage = $this->getUsage();
        return ($usage['used'] + $additionalSize) > $usage['quota'];
    }

    /**
     * Create tenant-specific directory
     * 
     * @param string $directory Directory path
     * @param int $permissions Directory permissions
     * @return bool Success status
     */
    public function createDirectory(string $directory, int $permissions = 0755): bool
    {
        $fullPath = $this->getPath($directory);
        
        if (!is_dir($fullPath)) {
            return mkdir($fullPath, $permissions, true);
        }
        
        return true;
    }

    /**
     * Delete tenant file or directory
     * 
     * @param string $path Path to delete
     * @return bool Success status
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->getPath($path);
        
        if (is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        }
        
        return false;
    }

    /**
     * Build tenant-specific storage path
     * 
     * @param TenantInterface $tenant Target tenant
     * @return string Tenant storage path
     */
    protected function buildTenantPath(TenantInterface $tenant): string
    {
        return $this->basePath . '/' . $this->config['tenant_dir'] . '/' . $tenant->getId();
    }

    /**
     * Ensure tenant directories exist
     * 
     * @return void
     */
    protected function ensureTenantDirectories(): void
    {
        if (!$this->tenantPath) {
            return;
        }

        $directories = [
            $this->tenantPath,
            $this->tenantPath . '/uploads',
            $this->tenantPath . '/cache',
            $this->tenantPath . '/temp',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Calculate directory size recursively
     * 
     * @param string $directory Directory path
     * @return int Total size in bytes
     */
    protected function calculateDirectorySize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Parse storage quota string to bytes
     * 
     * @param string $quota Quota string (e.g., "100MB", "1GB")
     * @return int Quota in bytes
     */
    protected function parseStorageQuota(string $quota): int
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
     * Delete directory recursively
     * 
     * @param string $directory Directory path
     * @return bool Success status
     */
    protected function deleteDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($directory);
    }
}