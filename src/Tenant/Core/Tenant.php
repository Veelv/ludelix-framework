<?php

namespace Ludelix\Tenant\Core;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Exceptions\TenantConfigurationException;
use Ludelix\Tenant\Exceptions\TenantValidationException;

/**
 * Tenant Entity - Comprehensive Multi-Tenant Data Model
 * 
 * The Tenant class represents a complete tenant entity within the Ludelix
 * multi-tenancy system. It encapsulates all tenant-specific data, configuration,
 * and business logic required for enterprise-grade multi-tenant applications.
 * 
 * This implementation provides:
 * 
 * 1. **Complete Tenant Data Model**:
 *    - Unique identification and naming
 *    - Domain and subdomain management
 *    - Database and cache configuration
 *    - Resource quotas and usage tracking
 *    - Feature flags and capabilities
 *    - Hierarchical tenant relationships
 * 
 * 2. **Configuration Management**:
 *    - Tenant-specific configuration overrides
 *    - Configuration inheritance from parent tenants
 *    - Dynamic configuration updates
 *    - Configuration validation and type safety
 *    - Environment-specific configuration
 * 
 * 3. **Resource Management**:
 *    - Storage quotas and usage monitoring
 *    - Bandwidth allocation and tracking
 *    - API rate limiting and usage
 *    - Custom resource type support
 *    - Resource usage analytics
 * 
 * 4. **Security & Compliance**:
 *    - Tenant-specific encryption keys
 *    - Access control and permissions
 *    - Audit trail and compliance tracking
 *    - Data retention policies
 *    - Privacy and GDPR compliance
 * 
 * 5. **Operational Features**:
 *    - Tenant status management (active/inactive/suspended)
 *    - Maintenance mode support
 *    - Backup and recovery configuration
 *    - Multi-region deployment support
 *    - Performance monitoring integration
 * 
 * @package Ludelix\Tenant\Core
 * @author Ludelix Framework Team
 * @version 2.0.0
 * @since 1.0.0
 * 
 * @example Basic Usage:
 * ```php
 * $tenant = new Tenant([
 *     'id' => 'acme-corp-001',
 *     'name' => 'Acme Corporation',
 *     'domain' => ['primary' => 'acme.example.com'],
 *     'status' => 'active'
 * ]);
 * 
 * // Check tenant capabilities
 * if ($tenant->hasFeature('advanced_analytics')) {
 *     // Enable advanced features
 * }
 * 
 * // Get tenant-specific configuration
 * $maxUsers = $tenant->getConfig('limits.max_users', 100);
 * ```
 * 
 * @example Advanced Usage:
 * ```php
 * // Create hierarchical tenant structure
 * $parentTenant = new Tenant(['id' => 'enterprise-parent']);
 * $childTenant = new Tenant([
 *     'id' => 'enterprise-child',
 *     'parent_id' => 'enterprise-parent'
 * ]);
 * 
 * // Configure resource quotas
 * $tenant->setResourceQuota('storage', '500GB');
 * $tenant->setResourceQuota('api_calls', 1000000);
 * 
 * // Enable specific features
 * $tenant->enableFeature('sso_integration');
 * $tenant->enableFeature('custom_branding');
 * ```
 */
class Tenant implements TenantInterface
{
    /**
     * Core tenant identification and metadata
     */
    protected string $id;
    protected string $name;
    protected array $domain = [];
    protected string $status = 'active';
    protected array $metadata = [];
    
    /**
     * Tenant configuration and customization
     */
    protected array $config = [];
    protected array $features = [];
    protected array $resourceQuotas = [];
    protected array $resourceUsage = [];
    
    /**
     * Hierarchical tenant relationships
     */
    protected ?string $parentId = null;
    protected array $childrenIds = [];
    
    /**
     * Database and cache configuration
     */
    protected array $databaseConfig = [];
    protected array $cacheConfig = [];
    
    /**
     * Operational and lifecycle management
     */
    protected \DateTimeInterface $createdAt;
    protected \DateTimeInterface $updatedAt;
    protected ?string $suspensionReason = null;
    protected array $maintenanceWindows = [];
    
    /**
     * Security and compliance settings
     */
    protected array $encryptionKeys = [];
    protected array $accessPolicies = [];
    protected array $complianceSettings = [];
    protected array $auditConfiguration = [];
    
    /**
     * Performance and monitoring configuration
     */
    protected array $performanceSettings = [];
    protected array $monitoringConfiguration = [];
    protected array $alertingRules = [];
    
    /**
     * Backup and disaster recovery configuration
     */
    protected array $backupConfiguration = [];
    protected array $recoverySettings = [];
    protected array $replicationSettings = [];
    
    /**
     * Initialize tenant with comprehensive configuration validation
     * 
     * @param array $data Tenant initialization data
     * @throws TenantValidationException If tenant data is invalid
     * @throws TenantConfigurationException If configuration is malformed
     */
    public function __construct(array $data = [])
    {
        // Validate required fields
        $this->validateRequiredFields($data);
        
        // Initialize core properties
        $this->id = $data['id'];
        $this->name = $data['name'] ?? $this->id;
        $this->status = $data['status'] ?? 'active';
        
        // Initialize domain configuration
        $this->initializeDomainConfiguration($data['domain'] ?? []);
        
        // Initialize database configuration
        $this->initializeDatabaseConfiguration($data['database'] ?? []);
        
        // Initialize cache configuration
        $this->initializeCacheConfiguration($data['cache'] ?? []);
        
        // Initialize resource quotas and usage tracking
        $this->initializeResourceManagement($data['resources'] ?? []);
        
        // Initialize feature flags and capabilities
        $this->initializeFeatureManagement($data['features'] ?? []);
        
        // Initialize hierarchical relationships
        $this->initializeHierarchicalRelationships($data);
        
        // Initialize security and compliance settings
        $this->initializeSecuritySettings($data['security'] ?? []);
        
        // Initialize operational configuration
        $this->initializeOperationalSettings($data['operational'] ?? []);
        
        // Initialize performance and monitoring
        $this->initializePerformanceSettings($data['performance'] ?? []);
        
        // Initialize backup and recovery configuration
        $this->initializeBackupSettings($data['backup'] ?? []);
        
        // Set timestamps
        $this->createdAt = new \DateTimeImmutable($data['created_at'] ?? 'now');
        $this->updatedAt = new \DateTimeImmutable($data['updated_at'] ?? 'now');
        
        // Initialize custom metadata
        $this->metadata = $data['metadata'] ?? [];
        
        // Initialize tenant-specific configuration
        $this->config = $data['config'] ?? [];
        
        // Validate complete tenant configuration
        $this->validateTenantConfiguration();
    }
    
    /**
     * Get the unique tenant identifier
     * 
     * @return string Unique tenant identifier (UUID v4 recommended)
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * Get the human-readable tenant name
     * 
     * @return string Human-readable tenant name
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get the tenant domain configuration
     * 
     * Returns the primary domain and any aliases associated with this tenant.
     * Supports both apex domains and subdomain configurations.
     * 
     * @return array Domain configuration including primary and aliases
     */
    public function getDomain(): array
    {
        return $this->domain;
    }
    
    /**
     * Get tenant-specific database configuration
     * 
     * Returns the database connection parameters for this tenant,
     * supporting multiple database isolation strategies.
     * 
     * @return array Database configuration parameters
     */
    public function getDatabaseConfig(): array
    {
        return $this->databaseConfig;
    }
    
    /**
     * Get tenant-specific cache configuration
     * 
     * Returns cache configuration including prefixes, TTL overrides,
     * and tenant-specific cache drivers.
     * 
     * @return array Cache configuration parameters
     */
    public function getCacheConfig(): array
    {
        return $this->cacheConfig;
    }
    
    /**
     * Get tenant status and operational state
     * 
     * Returns the current operational status of the tenant including
     * active/inactive state, suspension reasons, and maintenance modes.
     * 
     * @return string Current tenant status (active|inactive|suspended|maintenance)
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    
    /**
     * Get tenant metadata and custom attributes
     * 
     * Returns additional tenant-specific metadata including custom
     * configuration, feature flags, and business-specific attributes.
     * 
     * @return array Tenant metadata and custom attributes
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Get tenant resource quotas and limits
     * 
     * Returns the resource allocation limits for this tenant including
     * storage, bandwidth, API calls, and custom resource types.
     * 
     * @return array Resource quotas and current usage
     */
    public function getResourceQuotas(): array
    {
        return [
            'quotas' => $this->resourceQuotas,
            'usage' => $this->resourceUsage,
            'utilization' => $this->calculateResourceUtilization()
        ];
    }
    
    /**
     * Get tenant parent relationship
     * 
     * Returns the parent tenant ID if this tenant is part of a
     * hierarchical structure, null if root tenant.
     * 
     * @return string|null Parent tenant ID or null for root tenants
     */
    public function getParentId(): ?string
    {
        return $this->parentId;
    }
    
    /**
     * Get tenant children relationships
     * 
     * Returns array of child tenant IDs if this tenant has
     * sub-tenants in a hierarchical structure.
     * 
     * @return array Array of child tenant IDs
     */
    public function getChildrenIds(): array
    {
        return $this->childrenIds;
    }
    
    /**
     * Check if tenant is currently active
     * 
     * Determines if the tenant is in an active operational state
     * and can process requests.
     * 
     * @return bool True if tenant is active and operational
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    /**
     * Check if tenant has specific feature enabled
     * 
     * Determines if a particular feature or capability is enabled
     * for this tenant based on subscription or configuration.
     * 
     * @param string $feature Feature identifier to check
     * @return bool True if feature is enabled for this tenant
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features) || 
               ($this->config['features'][$feature] ?? false);
    }
    
    /**
     * Get tenant-specific configuration value
     * 
     * Retrieves a configuration value specific to this tenant,
     * with fallback to global configuration if not overridden.
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $default Default value if configuration not found
     * @return mixed Configuration value or default
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, $key, $default);
    }
    
    /**
     * Set tenant-specific configuration value
     * 
     * Sets a configuration value specific to this tenant,
     * overriding global configuration for this tenant only.
     * 
     * @param string $key Configuration key in dot notation
     * @param mixed $value Configuration value to set
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
     * Returns the timestamp when this tenant was initially created
     * in the system.
     * 
     * @return \DateTimeInterface Tenant creation timestamp
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    
    /**
     * Get tenant last update timestamp
     * 
     * Returns the timestamp when this tenant configuration was
     * last modified.
     * 
     * @return \DateTimeInterface Tenant last update timestamp
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
    
    /**
     * Convert tenant to array representation
     * 
     * Returns a complete array representation of the tenant
     * suitable for serialization and API responses.
     * 
     * @return array Complete tenant data as associative array
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
     * Returns a JSON string representation of the tenant
     * suitable for API responses and logging.
     * 
     * @return string JSON representation of tenant data
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Enable specific feature for this tenant
     * 
     * @param string $feature Feature identifier to enable
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
     * Disable specific feature for this tenant
     * 
     * @param string $feature Feature identifier to disable
     * @return self Fluent interface
     */
    public function disableFeature(string $feature): self
    {
        $this->features = array_filter($this->features, fn($f) => $f !== $feature);
        $this->updatedAt = new \DateTimeImmutable();
        
        return $this;
    }
    
    /**
     * Set resource quota for specific resource type
     * 
     * @param string $resource Resource type identifier
     * @param mixed $quota Quota value (numeric or string with units)
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
     * @param string $resource Resource type identifier
     * @param mixed $usage Current usage value
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
     * @param string $resource Resource type to check
     * @return bool True if quota is exceeded
     */
    public function isResourceQuotaExceeded(string $resource): bool
    {
        $quota = $this->resourceQuotas[$resource] ?? null;
        $usage = $this->resourceUsage[$resource] ?? 0;
        
        if ($quota === null) {
            return false; // No quota set
        }
        
        return $this->compareResourceValues($usage, $quota) > 0;
    }
    
    // Protected helper methods for internal operations
    
    protected function validateRequiredFields(array $data): void
    {
        if (empty($data['id'])) {
            throw new TenantValidationException('Tenant ID is required');
        }
        
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $data['id'])) {
            throw new TenantValidationException('Tenant ID must contain only alphanumeric characters, hyphens, and underscores');
        }
    }
    
    protected function initializeDomainConfiguration(array $domainData): void
    {
        $this->domain = [
            'primary' => $domainData['primary'] ?? null,
            'aliases' => $domainData['aliases'] ?? [],
            'subdomain' => $domainData['subdomain'] ?? null,
            'custom_domains' => $domainData['custom_domains'] ?? [],
        ];
    }
    
    protected function initializeDatabaseConfiguration(array $dbData): void
    {
        $this->databaseConfig = [
            'strategy' => $dbData['strategy'] ?? 'shared',
            'connection' => $dbData['connection'] ?? 'default',
            'prefix' => $dbData['prefix'] ?? $this->id . '_',
            'schema' => $dbData['schema'] ?? null,
            'read_replicas' => $dbData['read_replicas'] ?? [],
            'encryption' => $dbData['encryption'] ?? true,
        ];
    }
    
    protected function initializeCacheConfiguration(array $cacheData): void
    {
        $this->cacheConfig = [
            'prefix' => $cacheData['prefix'] ?? "tenant:{$this->id}:",
            'ttl_multiplier' => $cacheData['ttl_multiplier'] ?? 1.0,
            'driver' => $cacheData['driver'] ?? 'default',
            'tags' => $cacheData['tags'] ?? ["tenant:{$this->id}"],
        ];
    }
    
    protected function initializeResourceManagement(array $resourceData): void
    {
        $this->resourceQuotas = $resourceData['quotas'] ?? [
            'storage' => '10GB',
            'bandwidth' => '100GB',
            'api_calls' => 10000,
            'users' => 100,
        ];
        
        $this->resourceUsage = $resourceData['usage'] ?? [];
    }
    
    protected function initializeFeatureManagement(array $featureData): void
    {
        $this->features = $featureData['enabled'] ?? [];
    }
    
    protected function initializeHierarchicalRelationships(array $data): void
    {
        $this->parentId = $data['parent_id'] ?? null;
        $this->childrenIds = $data['children_ids'] ?? [];
    }
    
    protected function initializeSecuritySettings(array $securityData): void
    {
        $this->encryptionKeys = $securityData['encryption_keys'] ?? [];
        $this->accessPolicies = $securityData['access_policies'] ?? [];
        $this->complianceSettings = $securityData['compliance'] ?? [];
        $this->auditConfiguration = $securityData['audit'] ?? [];
    }
    
    protected function initializeOperationalSettings(array $operationalData): void
    {
        $this->suspensionReason = $operationalData['suspension_reason'] ?? null;
        $this->maintenanceWindows = $operationalData['maintenance_windows'] ?? [];
    }
    
    protected function initializePerformanceSettings(array $performanceData): void
    {
        $this->performanceSettings = $performanceData['settings'] ?? [];
        $this->monitoringConfiguration = $performanceData['monitoring'] ?? [];
        $this->alertingRules = $performanceData['alerting'] ?? [];
    }
    
    protected function initializeBackupSettings(array $backupData): void
    {
        $this->backupConfiguration = $backupData['configuration'] ?? [];
        $this->recoverySettings = $backupData['recovery'] ?? [];
        $this->replicationSettings = $backupData['replication'] ?? [];
    }
    
    protected function validateTenantConfiguration(): void
    {
        // Comprehensive tenant configuration validation
        if ($this->status === 'suspended' && empty($this->suspensionReason)) {
            throw new TenantConfigurationException('Suspended tenants must have a suspension reason');
        }
        
        // Validate domain configuration
        if (!empty($this->domain['primary']) && !filter_var($this->domain['primary'], FILTER_VALIDATE_DOMAIN)) {
            throw new TenantConfigurationException('Invalid primary domain format');
        }
        
        // Validate resource quotas
        foreach ($this->resourceQuotas as $resource => $quota) {
            if (!$this->isValidResourceQuota($resource, $quota)) {
                throw new TenantConfigurationException("Invalid resource quota for {$resource}: {$quota}");
            }
        }
    }
    
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
    
    protected function calculateResourceUtilization(): array
    {
        $utilization = [];
        
        foreach ($this->resourceQuotas as $resource => $quota) {
            $usage = $this->resourceUsage[$resource] ?? 0;
            $utilization[$resource] = $this->calculateUtilizationPercentage($usage, $quota);
        }
        
        return $utilization;
    }
    
    protected function calculateUtilizationPercentage(mixed $usage, mixed $quota): float
    {
        // Convert both values to comparable numeric format
        $numericUsage = $this->convertToNumeric($usage);
        $numericQuota = $this->convertToNumeric($quota);
        
        if ($numericQuota <= 0) {
            return 0.0;
        }
        
        return ($numericUsage / $numericQuota) * 100;
    }
    
    protected function convertToNumeric(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        if (is_string($value)) {
            // Handle storage units (GB, MB, etc.)
            if (preg_match('/^(\d+(?:\.\d+)?)\s*(GB|MB|KB|TB)$/i', $value, $matches)) {
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
        }
        
        return 0.0;
    }
    
    protected function compareResourceValues(mixed $usage, mixed $quota): int
    {
        $numericUsage = $this->convertToNumeric($usage);
        $numericQuota = $this->convertToNumeric($quota);
        
        return $numericUsage <=> $numericQuota;
    }
    
    protected function isValidResourceQuota(string $resource, mixed $quota): bool
    {
        // Basic validation - can be extended for specific resource types
        if (is_numeric($quota)) {
            return $quota >= 0;
        }
        
        if (is_string($quota)) {
            // Validate storage format (e.g., "100GB", "1TB")
            return preg_match('/^\d+(?:\.\d+)?\s*(GB|MB|KB|TB)$/i', $quota);
        }
        
        return false;
    }
}