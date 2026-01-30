<?php
namespace Ludelix\Bridge\Context;

/**
 * Class TenantContext
 *
 * Handles tenant context information for multi-tenant applications.
 * Provides methods to set and retrieve tenant ID, metadata, and custom attributes.
 */
class TenantContext
{
    /**
     * The unique identifier for the tenant.
     *
     * @var string|null
     */
    protected ?string $tenantId = null;

    /**
     * Arbitrary metadata associated with the tenant.
     *
     * @var array
     */
    protected array $metadata = [];

    /**
     * Custom attributes for advanced tenant context scenarios.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Set the tenant unique identifier.
     *
     * @param string $tenantId
     * @return void
     */
    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Get the tenant unique identifier.
     *
     * @return string|null
     */
    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * Set metadata for the tenant.
     *
     * @param array $metadata
     * @return void
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * Get metadata for the tenant.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set a custom attribute for the tenant context.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a custom attribute by key.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Get all custom attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Reset the tenant context to its initial state.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->tenantId = null;
        $this->metadata = [];
        $this->attributes = [];
    }

    /**
     * Check if the tenant context is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->tenantId !== null;
    }
    
    /**
     * Create new instance with tenant
     */
    public function withTenant(string $tenantId): self
    {
        $clone = clone $this;
        $clone->setTenantId($tenantId);
        return $clone;
    }
    
    /**
     * Constructor with config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['tenant_id'])) {
            $this->tenantId = $config['tenant_id'];
        }
    }
}
