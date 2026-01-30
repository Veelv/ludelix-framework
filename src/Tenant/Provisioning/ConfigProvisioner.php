<?php

namespace Ludelix\Tenant\Provisioning;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Config Provisioner - Tenant Configuration Setup Manager
 * 
 * Handles configuration provisioning for tenants including environment setup,
 * feature flags, service configurations, and tenant-specific settings.
 * 
 * @package Ludelix\Tenant\Provisioning
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ConfigProvisioner
{
    /**
     * Configuration storage path
     */
    protected string $configPath;

    /**
     * Provisioning configuration
     */
    protected array $config;

    /**
     * Provisioned configurations tracking
     */
    protected array $provisionedConfigs = [];

    /**
     * Default tenant configuration templates
     */
    protected array $configTemplates = [];

    /**
     * Initialize configuration provisioner
     * 
     * @param array $config Provisioning configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'config_path' => 'config/tenants',
            'create_env_file' => true,
            'setup_cache_config' => true,
            'setup_logging' => true,
            'apply_feature_flags' => true,
        ], $config);
        
        $this->configPath = $this->config['config_path'];
        $this->initializeConfigTemplates();
    }

    /**
     * Provision configuration for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     * @return bool Success status
     * @throws \Exception If provisioning fails
     */
    public function provision(TenantInterface $tenant, array $options = []): bool
    {
        try {
            $tenantConfigPath = $this->buildTenantConfigPath($tenant);
            
            // Create tenant configuration directory
            $this->createConfigDirectory($tenantConfigPath);
            
            // Generate tenant configuration files
            $this->generateConfigFiles($tenant, $tenantConfigPath);
            
            // Setup environment configuration
            if ($this->config['create_env_file']) {
                $this->createEnvironmentConfig($tenant, $tenantConfigPath);
            }
            
            // Setup cache configuration
            if ($this->config['setup_cache_config']) {
                $this->setupCacheConfig($tenant, $tenantConfigPath);
            }
            
            // Setup logging configuration
            if ($this->config['setup_logging']) {
                $this->setupLoggingConfig($tenant, $tenantConfigPath);
            }
            
            // Apply feature flags
            if ($this->config['apply_feature_flags']) {
                $this->applyFeatureFlags($tenant, $tenantConfigPath);
            }
            
            // Track provisioned configuration
            $this->provisionedConfigs[$tenant->getId()] = [
                'path' => $tenantConfigPath,
                'files' => $this->getGeneratedFiles($tenantConfigPath),
                'provisioned_at' => date('Y-m-d H:i:s'),
            ];
            
            return true;
            
        } catch (\Throwable $e) {
            throw new \Exception("Configuration provisioning failed for tenant {$tenant->getId()}: " . $e->getMessage());
        }
    }

    /**
     * Deprovision configuration for tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function deprovision(string $tenantId): bool
    {
        if (!isset($this->provisionedConfigs[$tenantId])) {
            return true; // Already deprovisioned
        }

        try {
            $config = $this->provisionedConfigs[$tenantId];
            $tenantConfigPath = $config['path'];
            
            // Backup configuration before removal
            $this->backupTenantConfig($tenantId, $tenantConfigPath);
            
            // Remove tenant configuration directory
            $this->removeConfigDirectory($tenantConfigPath);
            
            unset($this->provisionedConfigs[$tenantId]);
            
            return true;
            
        } catch (\Throwable $e) {
            error_log("Configuration deprovisioning failed for tenant {$tenantId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get configuration provisioning status
     * 
     * @param string $tenantId Tenant identifier
     * @return array Status information
     */
    public function getStatus(string $tenantId): array
    {
        if (!isset($this->provisionedConfigs[$tenantId])) {
            return [
                'status' => 'not_provisioned',
                'message' => 'Configuration not provisioned',
                'path' => null,
            ];
        }

        $config = $this->provisionedConfigs[$tenantId];
        
        return [
            'status' => 'ready',
            'message' => 'Configuration provisioned successfully',
            'path' => $config['path'],
            'files' => $config['files'],
            'provisioned_at' => $config['provisioned_at'],
        ];
    }

    /**
     * Rollback configuration provisioning
     * 
     * @param string $tenantId Tenant identifier
     * @return bool Success status
     */
    public function rollback(string $tenantId): bool
    {
        return $this->deprovision($tenantId);
    }

    /**
     * Build tenant configuration path
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return string Configuration path
     */
    protected function buildTenantConfigPath(TenantInterface $tenant): string
    {
        return $this->configPath . '/' . $tenant->getId();
    }

    /**
     * Create configuration directory
     * 
     * @param string $configPath Configuration path
     */
    protected function createConfigDirectory(string $configPath): void
    {
        if (!is_dir($configPath)) {
            if (!mkdir($configPath, 0755, true)) {
                throw new \Exception("Failed to create configuration directory: {$configPath}");
            }
        }
    }

    /**
     * Generate configuration files for tenant
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function generateConfigFiles(TenantInterface $tenant, string $configPath): void
    {
        // Generate main tenant configuration
        $this->generateMainConfig($tenant, $configPath);
        
        // Generate database configuration
        $this->generateDatabaseConfig($tenant, $configPath);
        
        // Generate service configurations
        $this->generateServiceConfigs($tenant, $configPath);
    }

    /**
     * Generate main tenant configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function generateMainConfig(TenantInterface $tenant, string $configPath): void
    {
        $config = [
            'tenant' => [
                'id' => $tenant->getId(),
                'name' => $tenant->getName(),
                'status' => $tenant->getStatus(),
                'domain' => $tenant->getDomain(),
                'created_at' => $tenant->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
            'features' => $this->getTenantFeatures($tenant),
            'resources' => $tenant->getResourceQuotas(),
            'metadata' => $tenant->getMetadata(),
        ];

        $configFile = $configPath . '/tenant.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Generate database configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function generateDatabaseConfig(TenantInterface $tenant, string $configPath): void
    {
        $dbConfig = $tenant->getDatabaseConfig();
        
        $config = [
            'database' => [
                'strategy' => $dbConfig['strategy'] ?? 'prefix',
                'connection' => $dbConfig['connection'] ?? 'default',
                'prefix' => $dbConfig['prefix'] ?? $tenant->getId() . '_',
                'schema' => $dbConfig['schema'] ?? null,
            ],
            'migrations' => [
                'table' => ($dbConfig['prefix'] ?? $tenant->getId() . '_') . 'migrations',
                'auto_run' => true,
            ],
        ];

        $configFile = $configPath . '/database.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Generate service configurations
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function generateServiceConfigs(TenantInterface $tenant, string $configPath): void
    {
        $services = [
            'mail' => $this->generateMailConfig($tenant),
            'queue' => $this->generateQueueConfig($tenant),
            'storage' => $this->generateStorageConfig($tenant),
        ];

        foreach ($services as $service => $config) {
            $configFile = $configPath . "/{$service}.json";
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Create environment configuration file
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function createEnvironmentConfig(TenantInterface $tenant, string $configPath): void
    {
        $envVars = [
            'TENANT_ID' => $tenant->getId(),
            'TENANT_NAME' => $tenant->getName(),
            'TENANT_STATUS' => $tenant->getStatus(),
            'DB_PREFIX' => $tenant->getDatabaseConfig()['prefix'] ?? $tenant->getId() . '_',
            'CACHE_PREFIX' => $tenant->getCacheConfig()['prefix'] ?? "tenant:{$tenant->getId()}:",
        ];

        $envContent = '';
        foreach ($envVars as $key => $value) {
            $envContent .= "{$key}={$value}\n";
        }

        $envFile = $configPath . '/.env';
        file_put_contents($envFile, $envContent);
    }

    /**
     * Setup cache configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function setupCacheConfig(TenantInterface $tenant, string $configPath): void
    {
        $cacheConfig = $tenant->getCacheConfig();
        
        $config = [
            'cache' => [
                'prefix' => $cacheConfig['prefix'] ?? "tenant:{$tenant->getId()}:",
                'ttl_multiplier' => $cacheConfig['ttl_multiplier'] ?? 1.0,
                'driver' => $cacheConfig['driver'] ?? 'default',
                'tags' => ["tenant:{$tenant->getId()}"],
            ],
        ];

        $configFile = $configPath . '/cache.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Setup logging configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function setupLoggingConfig(TenantInterface $tenant, string $configPath): void
    {
        $config = [
            'logging' => [
                'channel' => "tenant.{$tenant->getId()}",
                'path' => "logs/tenants/{$tenant->getId()}.log",
                'level' => 'info',
                'context' => [
                    'tenant_id' => $tenant->getId(),
                    'tenant_name' => $tenant->getName(),
                ],
            ],
        ];

        $configFile = $configPath . '/logging.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Apply feature flags configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param string $configPath Configuration path
     */
    protected function applyFeatureFlags(TenantInterface $tenant, string $configPath): void
    {
        $features = $this->getTenantFeatures($tenant);
        
        $config = [
            'features' => $features,
            'feature_flags' => array_fill_keys($features, true),
        ];

        $configFile = $configPath . '/features.json';
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Get tenant features list
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return array Features list
     */
    protected function getTenantFeatures(TenantInterface $tenant): array
    {
        $features = [];
        $metadata = $tenant->getMetadata();
        
        if (isset($metadata['features'])) {
            $features = $metadata['features'];
        }
        
        // Add default features based on tenant status
        if ($tenant->isActive()) {
            $features[] = 'basic_access';
        }
        
        return array_unique($features);
    }

    /**
     * Generate mail configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return array Mail configuration
     */
    protected function generateMailConfig(TenantInterface $tenant): array
    {
        return [
            'mail' => [
                'from' => [
                    'address' => "noreply@{$tenant->getId()}.app.com",
                    'name' => $tenant->getName(),
                ],
                'subject_prefix' => "[{$tenant->getName()}] ",
            ],
        ];
    }

    /**
     * Generate queue configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return array Queue configuration
     */
    protected function generateQueueConfig(TenantInterface $tenant): array
    {
        return [
            'queue' => [
                'prefix' => "tenant.{$tenant->getId()}",
                'connection' => 'default',
                'retry_after' => 90,
            ],
        ];
    }

    /**
     * Generate storage configuration
     * 
     * @param TenantInterface $tenant Tenant instance
     * @return array Storage configuration
     */
    protected function generateStorageConfig(TenantInterface $tenant): array
    {
        return [
            'storage' => [
                'disk' => 'tenant',
                'path' => "tenants/{$tenant->getId()}",
                'url' => "/storage/tenants/{$tenant->getId()}",
            ],
        ];
    }

    /**
     * Get list of generated configuration files
     * 
     * @param string $configPath Configuration path
     * @return array File list
     */
    protected function getGeneratedFiles(string $configPath): array
    {
        if (!is_dir($configPath)) {
            return [];
        }

        $files = [];
        $iterator = new \DirectoryIterator($configPath);
        
        foreach ($iterator as $item) {
            if ($item->isDot()) continue;
            if ($item->isFile()) {
                $files[] = $item->getFilename();
            }
        }
        
        return $files;
    }

    /**
     * Backup tenant configuration
     * 
     * @param string $tenantId Tenant identifier
     * @param string $configPath Configuration path
     */
    protected function backupTenantConfig(string $tenantId, string $configPath): void
    {
        $backupPath = $this->configPath . '/backups/tenant_' . $tenantId . '_' . date('Y-m-d_H-i-s');
        
        if (is_dir($configPath)) {
            mkdir($backupPath, 0755, true);
            
            // Copy configuration files to backup
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($configPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                $targetPath = $backupPath . '/' . $iterator->getSubPathName();
                $targetDir = dirname($targetPath);
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                copy($file->getRealPath(), $targetPath);
            }
        }
    }

    /**
     * Remove configuration directory
     * 
     * @param string $configPath Configuration path
     */
    protected function removeConfigDirectory(string $configPath): void
    {
        if (!is_dir($configPath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($configPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($configPath);
    }

    /**
     * Initialize configuration templates
     */
    protected function initializeConfigTemplates(): void
    {
        // This would load configuration templates from files
        // For now, templates are generated dynamically
        $this->configTemplates = [];
    }
}