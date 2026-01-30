<?php

namespace Ludelix\Interface\Tenant;

/**
 * Tenant Interface - Multi-Tenant Architecture Contract
 * 
 * Defines the comprehensive contract for enterprise-grade multi-tenant applications.
 * Supports multiple tenancy models, isolation strategies, and advanced features.
 * 
 * @package Ludelix\Interface\Tenant
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
interface TenantInterface
{
    /**
     * Get the unique tenant identifier
     * 
     * Returns the globally unique identifier for this tenant instance.
     * This ID is used across all systems for tenant isolation and
     * resource allocation.
     * 
     * @return string Unique tenant identifier (UUID v4 recommended)
     */
    public function getId(): string;
    
    /**
     * Get the human-readable tenant name
     * 
     * Returns the display name for this tenant, used in administrative
     * interfaces and user-facing contexts.
     * 
     * @return string Human-readable tenant name
     */
    public function getName(): string;
    
    /**
     * Get the tenant domain configuration
     * 
     * Returns the primary domain and any aliases associated with this tenant.
     * Supports both apex domains and subdomain configurations.
     * 
     * @return array Domain configuration including primary and aliases
     */
    public function getDomain(): array;
    
    /**
     * Get tenant-specific database configuration
     * 
     * Returns the database connection parameters for this tenant,
     * supporting multiple database isolation strategies.
     * 
     * @return array Database configuration parameters
     */
    public function getDatabaseConfig(): array;
    
    /**
     * Get tenant-specific cache configuration
     * 
     * Returns cache configuration including prefixes, TTL overrides,
     * and tenant-specific cache drivers.
     * 
     * @return array Cache configuration parameters
     */
    public function getCacheConfig(): array;
    
    /**
     * Get tenant status and operational state
     * 
     * Returns the current operational status of the tenant including
     * active/inactive state, suspension reasons, and maintenance modes.
     * 
     * @return string Current tenant status (active|inactive|suspended|maintenance)
     */
    public function getStatus(): string;
    
    /**
     * Get tenant metadata and custom attributes
     * 
     * Returns additional tenant-specific metadata including custom
     * configuration, feature flags, and business-specific attributes.
     * 
     * @return array Tenant metadata and custom attributes
     */
    public function getMetadata(): array;
    
    /**
     * Get tenant resource quotas and limits
     * 
     * Returns the resource allocation limits for this tenant including
     * storage, bandwidth, API calls, and custom resource types.
     * 
     * @return array Resource quotas and current usage
     */
    public function getResourceQuotas(): array;
    
    /**
     * Get tenant parent relationship
     * 
     * Returns the parent tenant ID if this tenant is part of a
     * hierarchical structure, null if root tenant.
     * 
     * @return string|null Parent tenant ID or null for root tenants
     */
    public function getParentId(): ?string;
    
    /**
     * Get tenant children relationships
     * 
     * Returns array of child tenant IDs if this tenant has
     * sub-tenants in a hierarchical structure.
     * 
     * @return array Array of child tenant IDs
     */
    public function getChildrenIds(): array;
    
    /**
     * Check if tenant is currently active
     * 
     * Determines if the tenant is in an active operational state
     * and can process requests.
     * 
     * @return bool True if tenant is active and operational
     */
    public function isActive(): bool;
    
    /**
     * Check if tenant has specific feature enabled
     * 
     * Determines if a particular feature or capability is enabled
     * for this tenant based on subscription or configuration.
     * 
     * @param string $feature Feature identifier to check
     * @return bool True if feature is enabled for this tenant
     */
    public function hasFeature(string $feature): bool;
    
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
    public function getConfig(string $key, mixed $default = null): mixed;
    
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
    public function setConfig(string $key, mixed $value): void;
    
    /**
     * Get tenant creation timestamp
     * 
     * Returns the timestamp when this tenant was initially created
     * in the system.
     * 
     * @return \DateTimeInterface Tenant creation timestamp
     */
    public function getCreatedAt(): \DateTimeInterface;
    
    /**
     * Get tenant last update timestamp
     * 
     * Returns the timestamp when this tenant configuration was
     * last modified.
     * 
     * @return \DateTimeInterface Tenant last update timestamp
     */
    public function getUpdatedAt(): \DateTimeInterface;
    
    /**
     * Convert tenant to array representation
     * 
     * Returns a complete array representation of the tenant
     * suitable for serialization and API responses.
     * 
     * @return array Complete tenant data as associative array
     */
    public function toArray(): array;
    
    /**
     * Convert tenant to JSON representation
     * 
     * Returns a JSON string representation of the tenant
     * suitable for API responses and logging.
     * 
     * @return string JSON representation of tenant data
     */
    public function toJson(): string;
}