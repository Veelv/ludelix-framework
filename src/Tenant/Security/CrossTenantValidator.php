<?php

namespace Ludelix\Tenant\Security;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Cross Tenant Validator - Cross-Tenant Data Access Prevention
 * 
 * Validates and prevents unauthorized cross-tenant data access by analyzing
 * data ownership, request context, and tenant boundaries.
 * 
 * @package Ludelix\Tenant\Security
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class CrossTenantValidator
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Validation rules configuration
     */
    protected array $config;

    /**
     * Validation violations log
     */
    protected array $violations = [];

    /**
     * Initialize cross-tenant validator
     * 
     * @param array $config Validation configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'strict_mode' => true,
            'log_violations' => true,
            'throw_on_violation' => true,
            'allowed_cross_tenant_operations' => [],
        ], $config);
    }

    /**
     * Set current tenant context
     * 
     * @param TenantInterface $tenant Current tenant
     * @return self Fluent interface
     */
    public function setCurrentTenant(TenantInterface $tenant): self
    {
        $this->currentTenant = $tenant;
        return $this;
    }

    /**
     * Validate data access for current tenant
     * 
     * @param mixed $data Data to validate
     * @param string $operation Operation type
     * @param array $context Additional context
     * @return bool True if access is allowed
     * @throws \Exception If cross-tenant access detected
     */
    public function validateAccess(mixed $data, string $operation, array $context = []): bool
    {
        if (!$this->currentTenant) {
            if ($this->config['strict_mode']) {
                throw new \Exception('No tenant context available for validation');
            }
            return true;
        }

        // Check if operation is allowed for cross-tenant access
        if ($this->isAllowedCrossTenantOperation($operation)) {
            return true;
        }

        // Validate data ownership
        $violation = $this->detectDataOwnershipViolation($data, $operation, $context);
        
        if ($violation) {
            $this->handleViolation($violation);
            return false;
        }

        return true;
    }

    /**
     * Validate array of data items
     * 
     * @param array $dataItems Array of data items
     * @param string $operation Operation type
     * @param array $context Additional context
     * @return bool True if all items are valid
     */
    public function validateBatch(array $dataItems, string $operation, array $context = []): bool
    {
        foreach ($dataItems as $index => $data) {
            $itemContext = array_merge($context, ['batch_index' => $index]);
            
            if (!$this->validateAccess($data, $operation, $itemContext)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate database query for tenant isolation
     * 
     * @param string $query SQL query
     * @param array $bindings Query bindings
     * @param string $operation Operation type
     * @return bool True if query is safe
     */
    public function validateQuery(string $query, array $bindings, string $operation): bool
    {
        if (!$this->currentTenant) {
            return true;
        }

        // Check for tenant_id in WHERE clause
        if (!$this->queryHasTenantFilter($query, $bindings)) {
            $violation = [
                'type' => 'missing_tenant_filter',
                'query' => $query,
                'operation' => $operation,
                'tenant_id' => $this->currentTenant->getId(),
                'timestamp' => microtime(true),
            ];

            $this->handleViolation($violation);
            return false;
        }

        return true;
    }

    /**
     * Get validation violations log
     * 
     * @return array Violations log
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * Clear violations log
     * 
     * @return self Fluent interface
     */
    public function clearViolations(): self
    {
        $this->violations = [];
        return $this;
    }

    /**
     * Check if operation allows cross-tenant access
     * 
     * @param string $operation Operation type
     * @return bool True if allowed
     */
    protected function isAllowedCrossTenantOperation(string $operation): bool
    {
        return in_array($operation, $this->config['allowed_cross_tenant_operations']);
    }

    /**
     * Detect data ownership violation
     * 
     * @param mixed $data Data to check
     * @param string $operation Operation type
     * @param array $context Additional context
     * @return array|null Violation details or null
     */
    protected function detectDataOwnershipViolation(mixed $data, string $operation, array $context): ?array
    {
        $currentTenantId = $this->currentTenant->getId();
        
        // Check array data
        if (is_array($data)) {
            return $this->checkArrayDataOwnership($data, $currentTenantId, $operation, $context);
        }

        // Check object data
        if (is_object($data)) {
            return $this->checkObjectDataOwnership($data, $currentTenantId, $operation, $context);
        }

        return null;
    }

    /**
     * Check array data ownership
     * 
     * @param array $data Array data
     * @param string $currentTenantId Current tenant ID
     * @param string $operation Operation type
     * @param array $context Context
     * @return array|null Violation details
     */
    protected function checkArrayDataOwnership(array $data, string $currentTenantId, string $operation, array $context): ?array
    {
        // Check for tenant_id field
        if (isset($data['tenant_id']) && $data['tenant_id'] !== $currentTenantId) {
            return [
                'type' => 'cross_tenant_data_access',
                'data_tenant_id' => $data['tenant_id'],
                'current_tenant_id' => $currentTenantId,
                'operation' => $operation,
                'context' => $context,
                'timestamp' => microtime(true),
            ];
        }

        // Check for nested tenant references
        foreach ($data as $key => $value) {
            if (str_contains($key, 'tenant') && is_string($value) && $value !== $currentTenantId) {
                return [
                    'type' => 'nested_tenant_reference',
                    'field' => $key,
                    'data_tenant_id' => $value,
                    'current_tenant_id' => $currentTenantId,
                    'operation' => $operation,
                    'context' => $context,
                    'timestamp' => microtime(true),
                ];
            }
        }

        return null;
    }

    /**
     * Check object data ownership
     * 
     * @param object $data Object data
     * @param string $currentTenantId Current tenant ID
     * @param string $operation Operation type
     * @param array $context Context
     * @return array|null Violation details
     */
    protected function checkObjectDataOwnership(object $data, string $currentTenantId, string $operation, array $context): ?array
    {
        // Check if object has getTenantId method
        if (method_exists($data, 'getTenantId')) {
            $dataTenantId = $data->getTenantId();
            if ($dataTenantId && $dataTenantId !== $currentTenantId) {
                return [
                    'type' => 'object_tenant_mismatch',
                    'data_tenant_id' => $dataTenantId,
                    'current_tenant_id' => $currentTenantId,
                    'object_class' => get_class($data),
                    'operation' => $operation,
                    'context' => $context,
                    'timestamp' => microtime(true),
                ];
            }
        }

        // Check object properties
        if (property_exists($data, 'tenant_id')) {
            $dataTenantId = $data->tenant_id;
            if ($dataTenantId && $dataTenantId !== $currentTenantId) {
                return [
                    'type' => 'object_property_mismatch',
                    'data_tenant_id' => $dataTenantId,
                    'current_tenant_id' => $currentTenantId,
                    'object_class' => get_class($data),
                    'operation' => $operation,
                    'context' => $context,
                    'timestamp' => microtime(true),
                ];
            }
        }

        return null;
    }

    /**
     * Check if query has tenant filter
     * 
     * @param string $query SQL query
     * @param array $bindings Query bindings
     * @return bool True if has tenant filter
     */
    protected function queryHasTenantFilter(string $query, array $bindings): bool
    {
        $currentTenantId = $this->currentTenant->getId();
        
        // Check for tenant_id in WHERE clause
        if (preg_match('/WHERE.*tenant_id\s*=\s*[\'"]?' . preg_quote($currentTenantId) . '[\'"]?/i', $query)) {
            return true;
        }

        // Check for tenant_id in bindings
        foreach ($bindings as $binding) {
            if ($binding === $currentTenantId) {
                return true;
            }
        }

        // Check for table prefix (if using prefix strategy)
        $dbConfig = $this->currentTenant->getDatabaseConfig();
        if (isset($dbConfig['prefix'])) {
            $prefix = $dbConfig['prefix'];
            if (str_contains($query, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle validation violation
     * 
     * @param array $violation Violation details
     * @throws \Exception If configured to throw
     */
    protected function handleViolation(array $violation): void
    {
        // Log violation
        if ($this->config['log_violations']) {
            $this->violations[] = $violation;
        }

        // Throw exception if configured
        if ($this->config['throw_on_violation']) {
            $message = "Cross-tenant access violation detected: {$violation['type']}";
            if (isset($violation['data_tenant_id'])) {
                $message .= " (attempted access to tenant {$violation['data_tenant_id']} from tenant {$violation['current_tenant_id']})";
            }
            throw new \Exception($message);
        }
    }
}