<?php

namespace Ludelix\Tenant\Core;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Tenant Context - Current Tenant State Management
 * 
 * Manages the current tenant context state throughout the application lifecycle.
 * Provides thread-safe access to current tenant information and context switching.
 * 
 * @package Ludelix\Tenant\Core
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantContext
{
    /**
     * Current active tenant
     */
    protected static ?TenantInterface $currentTenant = null;

    /**
     * Tenant context stack for nested operations
     */
    protected static array $contextStack = [];

    /**
     * Context metadata
     */
    protected static array $metadata = [];

    /**
     * Set current tenant context
     * 
     * @param TenantInterface $tenant Tenant to set as current
     * @return void
     */
    public static function set(TenantInterface $tenant): void
    {
        self::$currentTenant = $tenant;
        self::$metadata['switched_at'] = microtime(true);
        self::$metadata['tenant_id'] = $tenant->getId();
    }

    /**
     * Get current tenant context
     * 
     * @return TenantInterface|null Current tenant or null
     */
    public static function get(): ?TenantInterface
    {
        return self::$currentTenant;
    }

    /**
     * Check if tenant context is active
     * 
     * @return bool True if tenant context exists
     */
    public static function has(): bool
    {
        return self::$currentTenant !== null;
    }

    /**
     * Get current tenant ID
     * 
     * @return string|null Current tenant ID or null
     */
    public static function id(): ?string
    {
        return self::$currentTenant?->getId();
    }

    /**
     * Push current tenant to stack and set new tenant
     * 
     * @param TenantInterface $tenant New tenant context
     * @return void
     */
    public static function push(TenantInterface $tenant): void
    {
        if (self::$currentTenant) {
            self::$contextStack[] = self::$currentTenant;
        }
        self::set($tenant);
    }

    /**
     * Pop tenant from stack and restore previous context
     * 
     * @return TenantInterface|null Restored tenant or null
     */
    public static function pop(): ?TenantInterface
    {
        if (!empty(self::$contextStack)) {
            $previous = array_pop(self::$contextStack);
            self::set($previous);
            return $previous;
        }
        
        self::clear();
        return null;
    }

    /**
     * Clear current tenant context
     * 
     * @return void
     */
    public static function clear(): void
    {
        self::$currentTenant = null;
        self::$metadata = [];
    }

    /**
     * Get context metadata
     * 
     * @return array Context metadata
     */
    public static function getMetadata(): array
    {
        return self::$metadata;
    }
}