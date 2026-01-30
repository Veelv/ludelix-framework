<?php

namespace Ludelix\Tenant\Isolation;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Config Isolation - Tenant Configuration Isolation Manager
 * 
 * Manages configuration isolation for multi-tenant applications including
 * tenant-specific settings, environment variables, and configuration overrides.
 * 
 * @package Ludelix\Tenant\Isolation
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class ConfigIsolation
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Base configuration
     */
    protected array $baseConfig = [];

    /**
     * Tenant-specific configurations
     */
    protected array $tenantConfigs = [];

    /**
     * Configuration cache
     */
    protected array $configCache = [];

    /**
     * Isolation configuration
     */
    protected array $config;

    /**
     * Initialize configuration isolation manager
     * 
     * @param array $baseConfig Base application configuration
     * @param array $config Isolation configuration
     */
    public function __construct(array $baseConfig = [], array $config = [])
    {
        $this->baseConfig = $baseConfig;
        $this->config = array_merge([
            'config_path' => 'config/tenants',
            'cache_enabled' => true,
            'override_env' => true,
            'allowed_overrides' => ['*'], // Allow all by default
            'protected_keys' => ['app.key', 'database.password'],
        ], $config);
    }

    /**
     * Switch configuration context to specific tenant
     * 
     * @param TenantInterface $tenant Target tenant
     * @return self Fluent interface
     */
    public function switchTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        
        // Load tenant-specific configuration
        $this->loadTenantConfig($tenant);
        
        // Apply environment overrides if enabled
        if ($this->config['override_env']) {
            $this->applyEnvironmentOverrides($tenant);
        }
        
        return $this;
    }

    /**
     * Get configuration value with tenant-specific override
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check cache first
        $cacheKey = $this->generateCacheKey($key);
        if ($this->config['cache_enabled'] && isset($this->configCache[$cacheKey])) {
            return $this->configCache[$cacheKey];
        }

        $value = $this->resolveConfigValue($key, $default);
        
        // Cache the result
        if ($this->config['cache_enabled']) {
            $this->configCache[$cacheKey] = $value;
        }
        
        return $value;
    }

    /**
     * Set tenant-specific configuration value
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $value Configuration value
     * @return self Fluent interface
     * @throws \Exception If key is protected
     */
    public function set(string $key, mixed $value): self
    {
        if ($this->isProtectedKey($key)) {
            throw new \Exception("Configuration key '{$key}' is protected and cannot be overridden");
        }

        if (!$this->isAllowedOverride($key)) {
            throw new \Exception("Configuration key '{$key}' is not allowed to be overridden");
        }

        $tenantId = $this->currentTenant?->getId() ?? 'default';
        
        if (!isset($this->tenantConfigs[$tenantId])) {
            $this->tenantConfigs[$tenantId] = [];
        }

        $this->setNestedValue($this->tenantConfigs[$tenantId], $key, $value);
        
        // Clear cache for this key
        $this->clearCacheForKey($key);
        
        return $this;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get all configuration for current tenant
     * 
     * @return array Complete configuration
     */
    public function all(): array
    {
        $tenantId = $this->currentTenant?->getId();
        $tenantConfig = $tenantId ? ($this->tenantConfigs[$tenantId] ?? []) : [];
        
        return $this->mergeConfigurations($this->baseConfig, $tenantConfig);
    }

    /**
     * Get tenant-specific configuration only
     * 
     * @param string|null $tenantId Tenant ID (null for current)
     * @return array Tenant-specific configuration
     */
    public function getTenantConfig(?string $tenantId = null): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }
        
        return $this->tenantConfigs[$targetTenantId] ?? [];
    }

    /**
     * Save tenant configuration to file
     * 
     * @param string|null $tenantId Tenant ID (null for current)
     * @return bool Success status
     */
    public function saveTenantConfig(?string $tenantId = null): bool
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return false;
        }

        $configFile = $this->getTenantConfigFile($targetTenantId);
        $config = $this->tenantConfigs[$targetTenantId] ?? [];
        
        $configDir = dirname($configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Clear configuration cache
     * 
     * @return self Fluent interface
     */
    public function clearCache(): self
    {
        $this->configCache = [];
        return $this;
    }

    /**
     * Get current tenant context
     * 
     * @return TenantInterface|null Current tenant
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    /**
     * Load tenant-specific configuration
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function loadTenantConfig(TenantInterface $tenant): void
    {
        $tenantId = $tenant->getId();
        
        if (isset($this->tenantConfigs[$tenantId])) {
            return; // Already loaded
        }

        // Load from file
        $configFile = $this->getTenantConfigFile($tenantId);
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            $this->tenantConfigs[$tenantId] = is_array($config) ? $config : [];
        } else {
            $this->tenantConfigs[$tenantId] = [];
        }

        // Merge with tenant's own configuration
        $tenantConfig = $tenant->getConfig('app', []);
        if (!empty($tenantConfig)) {
            $this->tenantConfigs[$tenantId] = $this->mergeConfigurations(
                $this->tenantConfigs[$tenantId],
                $tenantConfig
            );
        }
    }

    /**
     * Apply environment variable overrides
     * 
     * @param TenantInterface $tenant Target tenant
     */
    protected function applyEnvironmentOverrides(TenantInterface $tenant): void
    {
        $tenantId = $tenant->getId();
        $envPrefix = 'TENANT_' . strtoupper($tenantId) . '_';
        
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $envPrefix)) {
                $configKey = strtolower(str_replace($envPrefix, '', $key));
                $configKey = str_replace('_', '.', $configKey);
                
                if ($this->isAllowedOverride($configKey) && !$this->isProtectedKey($configKey)) {
                    $this->set($configKey, $value);
                }
            }
        }
    }

    /**
     * Resolve configuration value with tenant override
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Resolved value
     */
    protected function resolveConfigValue(string $key, mixed $default): mixed
    {
        $tenantId = $this->currentTenant?->getId();
        
        // Check tenant-specific configuration first
        if ($tenantId && isset($this->tenantConfigs[$tenantId])) {
            $tenantValue = $this->getNestedValue($this->tenantConfigs[$tenantId], $key);
            if ($tenantValue !== null) {
                return $tenantValue;
            }
        }
        
        // Fall back to base configuration
        $baseValue = $this->getNestedValue($this->baseConfig, $key);
        return $baseValue !== null ? $baseValue : $default;
    }

    /**
     * Get nested value from array using dot notation
     * 
     * @param array $array Source array
     * @param string $key Dot notation key
     * @return mixed Found value or null
     */
    protected function getNestedValue(array $array, string $key): mixed
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Set nested value in array using dot notation
     * 
     * @param array $array Target array
     * @param string $key Dot notation key
     * @param mixed $value Value to set
     */
    protected function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    /**
     * Merge two configuration arrays
     * 
     * @param array $base Base configuration
     * @param array $override Override configuration
     * @return array Merged configuration
     */
    protected function mergeConfigurations(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfigurations($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        
        return $base;
    }

    /**
     * Check if configuration key is protected
     * 
     * @param string $key Configuration key
     * @return bool True if protected
     */
    protected function isProtectedKey(string $key): bool
    {
        return in_array($key, $this->config['protected_keys']);
    }

    /**
     * Check if configuration key is allowed to be overridden
     * 
     * @param string $key Configuration key
     * @return bool True if allowed
     */
    protected function isAllowedOverride(string $key): bool
    {
        $allowedOverrides = $this->config['allowed_overrides'];
        
        // Allow all if wildcard is present
        if (in_array('*', $allowedOverrides)) {
            return true;
        }
        
        // Check exact matches
        if (in_array($key, $allowedOverrides)) {
            return true;
        }
        
        // Check pattern matches
        foreach ($allowedOverrides as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
                if (preg_match($regex, $key)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Generate cache key for configuration
     * 
     * @param string $key Configuration key
     * @return string Cache key
     */
    protected function generateCacheKey(string $key): string
    {
        $tenantId = $this->currentTenant?->getId() ?? 'default';
        return "{$tenantId}:{$key}";
    }

    /**
     * Clear cache for specific key
     * 
     * @param string $key Configuration key
     */
    protected function clearCacheForKey(string $key): void
    {
        $cacheKey = $this->generateCacheKey($key);
        unset($this->configCache[$cacheKey]);
    }

    /**
     * Get tenant configuration file path
     * 
     * @param string $tenantId Tenant ID
     * @return string Configuration file path
     */
    protected function getTenantConfigFile(string $tenantId): string
    {
        return $this->config['config_path'] . "/{$tenantId}/config.json";
    }
}