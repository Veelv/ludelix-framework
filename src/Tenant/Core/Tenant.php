<?php

namespace Ludelix\Tenant\Core;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Tenant Entity - Multi-Tenant Data Model
 * 
 * Represents a complete tenant entity with comprehensive configuration,
 * resource management, and hierarchical relationships for enterprise
 * multi-tenant applications.
 * 
 * @package Ludelix\Tenant\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class Tenant implements TenantInterface
{
    /**
     * Unique tenant identifier
     */
    protected string $id;

    /**
     * Human-readable tenant name
     */
    protected string $name;

    /**
     * Tenant domain configuration
     */
    protected array $domain = [];

    /**
     * Current tenant status
     */
    protected string $status = 'active';

    /**
     * Tenant metadata and custom attributes
     */
    protected array $metadata = [];

    /**
     * Tenant-specific configuration
     */
    protected array $config = [];

    /**
     * Enabled features for this tenant
     */
    protected array $features = [];

    /**
     * Resource quotas and limits
     */
    protected array $resourceQuotas = [];

    /**
     * Current resource usage
     */
    protected array $resourceUsage = [];

    /**
     * Parent tenant ID for hierarchical structure
     */
    protected ?string $parentId = null;

    /**
     * Child tenant IDs
     */
    protected array $childrenIds = [];

    /**
     * Database configuration for this tenant
     */
    protected array $databaseConfig = [];

    /**
     * Cache configuration for this tenant
     */
    protected array $cacheConfig = [];

    /**
     * Tenant creation timestamp
     */
    protected \DateTimeInterface $createdAt;

    /**
     * Last update timestamp
     */
    protected \DateTimeInterface $updatedAt;

    /**
     * Initialize tenant with configuration data
     * 
     * @param array $data Tenant initialization data
     * @throws \InvalidArgumentException If required data is missing
     */
    public function __construct(array $data = [])
    {
        $this->validateRequiredFields($data);
        
        $this->id = $data['id'];
        $this->name = $data['name'] ?? $this->id;
        $this->status = $data['status'] ?? 'active';
        
        $this->initializeDomainConfiguration($data['domain'] ?? []);
        $this->initializeDatabaseConfiguration($data['database'] ?? []);
        $this->initializeCacheConfiguration($data['cache'] ?? []);
        $this->initializeResourceManagement($data['resources'] ?? []);
        $this->initializeFeatureManagement($data['features'] ?? []);
        $this->initializeHierarchy($data);
        
        $this->createdAt = new \DateTimeImmutable($data['created_at'] ?? 'now');
        $this->updatedAt = new \DateTimeImmutable($data['updated_at'] ?? 'now');
        
        $this->metadata = $data['metadata'] ?? [];
        $this->config = $data['config'] ?? [];
    }

    /**
     * Get unique tenant identifier
     * 
     * @return string Tenant ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get human-readable tenant name
     * 
     * @return string Tenant name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get tenant domain configuration
     * 
     * @return array Domain configuration
     */
    public function getDomain(): array
    {
        return $this->domain;
    }

    /**
     * Get tenant-specific database configuration
     * 
     * @return array Database configuration
     */
    public function getDatabaseConfig(): array
    {
        return $this->databaseConfig;
    }

    /**
     * Get tenant-specific cache configuration
     * 
     * @return array Cache configuration
     */
    public function getCacheConfig(): array
    {
        return $this->cacheConfig;
    }

    /**
     * Get tenant operational status
     * 
     * @return string Current status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get tenant metadata and custom attributes
     * 
     * @return array Metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get resource quotas and current usage
     * 
     * @return array Resource information
     */
    public function getResourceQuotas(): array
    {
        return [
            'quotas' => $this->resourceQuotas,
            'usage' => $this->resourceUsage,
            'utilization' => $this->calculateUtilization()
        ];
    }

    /**
     * Get parent tenant ID
     * 
     * @return string|null Parent ID or null
     */
    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    /**
     * Get child tenant IDs
     * 
     * @return array Child IDs
     */
    public function getChildrenIds(): array
    {
        return $this->childrenIds;
    }

    /**
     * Check if tenant is active
     * 
     * @return bool True if active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant has specific feature enabled
     * 
     * @param string $feature Feature identifier
     * @return bool True if feature is enabled
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features) || 
               ($this->config['features'][$feature] ?? false);
    }

    /**
     * Get tenant-specific configuration value
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Set tenant-specific configuration value
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $value Configuration value
     * @return void
     */
    public function setConfig(string $key, mixed $value): void
    {
        $this->setNestedValue($this->config, $key, $value);
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Get tenant creation timestamp
     * 
     * @return \DateTimeInterface Creation timestamp
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Get last update timestamp
     * 
     * @return \DateTimeInterface Update timestamp
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Convert tenant to array representation
     * 
     * @return array Tenant data as array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'config' => $this->config,
            'features' => $this->features,
            'resource_quotas' => $this->getResourceQuotas(),
            'parent_id' => $this->parentId,
            'children_ids' => $this->childrenIds,
            'database_config' => $this->databaseConfig,
            'cache_config' => $this->cacheConfig,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ISO8601),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ISO8601),
        ];
    }

    /**
     * Convert tenant to JSON representation
     * 
     * @return string JSON representation
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Enable specific feature for tenant
     * 
     * @param string $feature Feature identifier
     * @return self Fluent interface
     */
    public function enableFeature(string $feature): self
    {
        if (!in_array($feature, $this->features)) {
            $this->features[] = $feature;
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    /**
     * Set resource quota for specific resource type
     * 
     * @param string $resource Resource type
     * @param mixed $quota Quota value
     * @return self Fluent interface
     */
    public function setResourceQuota(string $resource, mixed $quota): self
    {
        $this->resourceQuotas[$resource] = $quota;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Update resource usage for specific resource type
     * 
     * @param string $resource Resource type
     * @param mixed $usage Current usage
     * @return self Fluent interface
     */
    public function updateResourceUsage(string $resource, mixed $usage): self
    {
        $this->resourceUsage[$resource] = $usage;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Check if resource quota is exceeded
     * 
     * @param string $resource Resource type
     * @return bool True if quota exceeded
     */
    public function isResourceQuotaExceeded(string $resource): bool
    {
        $quota = $this->resourceQuotas[$resource] ?? null;
        $usage = $this->resourceUsage[$resource] ?? 0;
        
        if ($quota === null) {
            return false;
        }
        
        return $this->compareResourceValues($usage, $quota) > 0;
    }

    /**
     * Validate required fields during initialization
     * 
     * @param array $data Initialization data
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validateRequiredFields(array $data): void
    {
        if (empty($data['id'])) {
            throw new \InvalidArgumentException('Tenant ID is required');
        }
        
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $data['id'])) {
            throw new \InvalidArgumentException('Tenant ID must contain only alphanumeric characters, hyphens, and underscores');
        }
    }

    /**
     * Initialize domain configuration
     * 
     * @param array $domainData Domain configuration data
     */
    protected function initializeDomainConfiguration(array $domainData): void
    {
        $this->domain = [
            'primary' => $domainData['primary'] ?? null,
            'aliases' => $domainData['aliases'] ?? [],
            'subdomain' => $domainData['subdomain'] ?? null,
        ];
    }

    /**
     * Initialize database configuration
     * 
     * @param array $dbData Database configuration data
     */
    protected function initializeDatabaseConfiguration(array $dbData): void
    {
        $this->databaseConfig = [
            'strategy' => $dbData['strategy'] ?? 'shared',
            'connection' => $dbData['connection'] ?? 'default',
            'prefix' => $dbData['prefix'] ?? $this->id . '_',
            'schema' => $dbData['schema'] ?? null,
        ];
    }

    /**
     * Initialize cache configuration
     * 
     * @param array $cacheData Cache configuration data
     */
    protected function initializeCacheConfiguration(array $cacheData): void
    {
        $this->cacheConfig = [
            'prefix' => $cacheData['prefix'] ?? "tenant:{$this->id}:",
            'ttl_multiplier' => $cacheData['ttl_multiplier'] ?? 1.0,
            'driver' => $cacheData['driver'] ?? 'default',
        ];
    }

    /**
     * Initialize resource management
     * 
     * @param array $resourceData Resource configuration data
     */
    protected function initializeResourceManagement(array $resourceData): void
    {
        $this->resourceQuotas = $resourceData['quotas'] ?? [];
        $this->resourceUsage = $resourceData['usage'] ?? [];
    }

    /**
     * Initialize feature management
     * 
     * @param array $featureData Feature configuration data
     */
    protected function initializeFeatureManagement(array $featureData): void
    {
        $this->features = $featureData['enabled'] ?? [];
    }

    /**
     * Initialize hierarchical relationships
     * 
     * @param array $data Tenant data
     */
    protected function initializeHierarchy(array $data): void
    {
        $this->parentId = $data['parent_id'] ?? null;
        $this->childrenIds = $data['children_ids'] ?? [];
    }

    /**
     * Get nested value from array using dot notation
     * 
     * @param array $array Source array
     * @param string $key Dot notation key
     * @param mixed $default Default value
     * @return mixed Found value or default
     */
    protected function getNestedValue(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
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
     * Calculate resource utilization percentages
     * 
     * @return array Utilization data
     */
    protected function calculateUtilization(): array
    {
        $utilization = [];
        
        foreach ($this->resourceQuotas as $resource => $quota) {
            $usage = $this->resourceUsage[$resource] ?? 0;
            $utilization[$resource] = $this->calculateUtilizationPercentage($usage, $quota);
        }
        
        return $utilization;
    }

    /**
     * Calculate utilization percentage for resource
     * 
     * @param mixed $usage Current usage
     * @param mixed $quota Resource quota
     * @return float Utilization percentage
     */
    protected function calculateUtilizationPercentage(mixed $usage, mixed $quota): float
    {
        $numericUsage = $this->convertToNumeric($usage);
        $numericQuota = $this->convertToNumeric($quota);
        
        if ($numericQuota <= 0) {
            return 0.0;
        }
        
        return ($numericUsage / $numericQuota) * 100;
    }

    /**
     * Convert value to numeric for comparison
     * 
     * @param mixed $value Value to convert
     * @return float Numeric value
     */
    protected function convertToNumeric(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        if (is_string($value) && preg_match('/^(\d+(?:\.\d+)?)\s*(GB|MB|KB|TB)$/i', $value, $matches)) {
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            
            return match($unit) {
                'KB' => $number * 1024,
                'MB' => $number * 1024 * 1024,
                'GB' => $number * 1024 * 1024 * 1024,
                'TB' => $number * 1024 * 1024 * 1024 * 1024,
                default => $number
            };
        }
        
        return 0.0;
    }

    /**
     * Compare resource values for quota checking
     * 
     * @param mixed $usage Current usage
     * @param mixed $quota Resource quota
     * @return int Comparison result (-1, 0, 1)
     */
    protected function compareResourceValues(mixed $usage, mixed $quota): int
    {
        $numericUsage = $this->convertToNumeric($usage);
        $numericQuota = $this->convertToNumeric($quota);
        
        return $numericUsage <=> $numericQuota;
    }
}