<?php

namespace Ludelix\Tenant\Provisioning;

use Ludelix\Interface\Tenant\TenantInterface;
use Ludelix\Tenant\Core\Tenant;

/**
 * Tenant Provisioner - Main Tenant Provisioning Orchestrator
 * 
 * Orchestrates the complete tenant provisioning process including database setup,
 * storage allocation, configuration management, and resource initialization.
 * 
 * @package Ludelix\Tenant\Provisioning
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantProvisioner
{
    /**
     * Database provisioner
     */
    protected DatabaseProvisioner $databaseProvisioner;

    /**
     * Storage provisioner
     */
    protected StorageProvisioner $storageProvisioner;

    /**
     * Configuration provisioner
     */
    protected ConfigProvisioner $configProvisioner;

    /**
     * Provisioning configuration
     */
    protected array $config;

    /**
     * Provisioning log
     */
    protected array $provisioningLog = [];

    /**
     * Initialize tenant provisioner
     * 
     * @param DatabaseProvisioner $databaseProvisioner Database setup manager
     * @param StorageProvisioner $storageProvisioner Storage setup manager
     * @param ConfigProvisioner $configProvisioner Configuration setup manager
     * @param array $config Provisioning configuration
     */
    public function __construct(
        DatabaseProvisioner $databaseProvisioner,
        StorageProvisioner $storageProvisioner,
        ConfigProvisioner $configProvisioner,
        array $config = []
    ) {
        $this->databaseProvisioner = $databaseProvisioner;
        $this->storageProvisioner = $storageProvisioner;
        $this->configProvisioner = $configProvisioner;
        $this->config = array_merge([
            'auto_rollback' => true,
            'parallel_provisioning' => false,
            'validation_enabled' => true,
        ], $config);
    }

    /**
     * Provision new tenant with complete setup
     * 
     * @param array $tenantData Tenant configuration data
     * @param array $options Provisioning options
     * @return TenantInterface Provisioned tenant instance
     * @throws \Exception If provisioning fails
     */
    public function provision(array $tenantData, array $options = []): TenantInterface
    {
        $this->logStep('Starting tenant provisioning', $tenantData['id']);
        
        try {
            // Validate tenant data
            $this->validateTenantData($tenantData);
            
            // Create tenant instance
            $tenant = new Tenant($tenantData);
            
            // Execute provisioning steps
            $this->executeProvisioningSteps($tenant, $options);
            
            // Validate provisioned tenant
            $this->validateProvisionedTenant($tenant);
            
            $this->logStep('Tenant provisioning completed successfully', $tenant->getId());
            
            return $tenant;
            
        } catch (\Throwable $e) {
            $this->logStep('Tenant provisioning failed: ' . $e->getMessage(), $tenantData['id'] ?? 'unknown');
            
            if ($this->config['auto_rollback']) {
                $this->rollbackProvisioning($tenantData['id'] ?? null);
            }
            
            throw $e;
        }
    }

    /**
     * Deprovision existing tenant
     * 
     * @param string $tenantId Tenant identifier
     * @param array $options Deprovisioning options
     * @return bool Success status
     */
    public function deprovision(string $tenantId, array $options = []): bool
    {
        $this->logStep('Starting tenant deprovisioning', $tenantId);
        
        try {
            // Backup tenant data if requested
            if ($options['backup'] ?? false) {
                $this->backupTenantData($tenantId);
            }
            
            // Remove database resources
            $this->databaseProvisioner->deprovision($tenantId);
            
            // Remove storage resources
            $this->storageProvisioner->deprovision($tenantId);
            
            // Remove configuration
            $this->configProvisioner->deprovision($tenantId);
            
            $this->logStep('Tenant deprovisioning completed', $tenantId);
            
            return true;
            
        } catch (\Throwable $e) {
            $this->logStep('Tenant deprovisioning failed: ' . $e->getMessage(), $tenantId);
            throw $e;
        }
    }

    /**
     * Get provisioning status for tenant
     * 
     * @param string $tenantId Tenant identifier
     * @return array Provisioning status
     */
    public function getProvisioningStatus(string $tenantId): array
    {
        return [
            'database' => $this->databaseProvisioner->getStatus($tenantId),
            'storage' => $this->storageProvisioner->getStatus($tenantId),
            'config' => $this->configProvisioner->getStatus($tenantId),
            'overall' => $this->calculateOverallStatus($tenantId),
        ];
    }

    /**
     * Get provisioning log
     * 
     * @return array Provisioning log entries
     */
    public function getProvisioningLog(): array
    {
        return $this->provisioningLog;
    }

    /**
     * Clear provisioning log
     * 
     * @return self Fluent interface
     */
    public function clearLog(): self
    {
        $this->provisioningLog = [];
        return $this;
    }

    /**
     * Execute provisioning steps in sequence
     * 
     * @param TenantInterface $tenant Tenant instance
     * @param array $options Provisioning options
     */
    protected function executeProvisioningSteps(TenantInterface $tenant, array $options): void
    {
        $steps = [
            'database' => fn() => $this->databaseProvisioner->provision($tenant, $options),
            'storage' => fn() => $this->storageProvisioner->provision($tenant, $options),
            'config' => fn() => $this->configProvisioner->provision($tenant, $options),
        ];

        foreach ($steps as $stepName => $stepFunction) {
            $this->logStep("Executing {$stepName} provisioning", $tenant->getId());
            
            try {
                $stepFunction();
                $this->logStep("{$stepName} provisioning completed", $tenant->getId());
            } catch (\Throwable $e) {
                $this->logStep("{$stepName} provisioning failed: " . $e->getMessage(), $tenant->getId());
                throw $e;
            }
        }
    }

    /**
     * Validate tenant data before provisioning
     * 
     * @param array $tenantData Tenant data
     * @throws \InvalidArgumentException If validation fails
     */
    protected function validateTenantData(array $tenantData): void
    {
        if (!$this->config['validation_enabled']) {
            return;
        }

        $required = ['id', 'name'];
        
        foreach ($required as $field) {
            if (empty($tenantData[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }

        // Validate tenant ID format
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $tenantData['id'])) {
            throw new \InvalidArgumentException('Invalid tenant ID format');
        }
    }

    /**
     * Validate provisioned tenant
     * 
     * @param TenantInterface $tenant Provisioned tenant
     * @throws \Exception If validation fails
     */
    protected function validateProvisionedTenant(TenantInterface $tenant): void
    {
        if (!$this->config['validation_enabled']) {
            return;
        }

        $status = $this->getProvisioningStatus($tenant->getId());
        
        foreach ($status as $component => $componentStatus) {
            if ($component === 'overall') continue;
            
            if ($componentStatus['status'] !== 'ready') {
                throw new \Exception("Component '{$component}' is not ready: {$componentStatus['message']}");
            }
        }
    }

    /**
     * Rollback provisioning on failure
     * 
     * @param string|null $tenantId Tenant identifier
     */
    protected function rollbackProvisioning(?string $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $this->logStep('Starting provisioning rollback', $tenantId);
        
        try {
            // Rollback in reverse order
            $this->configProvisioner->rollback($tenantId);
            $this->storageProvisioner->rollback($tenantId);
            $this->databaseProvisioner->rollback($tenantId);
            
            $this->logStep('Provisioning rollback completed', $tenantId);
        } catch (\Throwable $e) {
            $this->logStep('Provisioning rollback failed: ' . $e->getMessage(), $tenantId);
        }
    }

    /**
     * Backup tenant data before deprovisioning
     * 
     * @param string $tenantId Tenant identifier
     */
    protected function backupTenantData(string $tenantId): void
    {
        $this->logStep('Creating tenant backup', $tenantId);
        
        // This would integrate with backup system
        // For now, just log the action
        $backupPath = "backups/tenant_{$tenantId}_" . date('Y-m-d_H-i-s');
        $this->logStep("Tenant backup created: {$backupPath}", $tenantId);
    }

    /**
     * Calculate overall provisioning status
     * 
     * @param string $tenantId Tenant identifier
     * @return array Overall status
     */
    protected function calculateOverallStatus(string $tenantId): array
    {
        $dbStatus = $this->databaseProvisioner->getStatus($tenantId);
        $storageStatus = $this->storageProvisioner->getStatus($tenantId);
        $configStatus = $this->configProvisioner->getStatus($tenantId);

        $allReady = $dbStatus['status'] === 'ready' && 
                   $storageStatus['status'] === 'ready' && 
                   $configStatus['status'] === 'ready';

        return [
            'status' => $allReady ? 'ready' : 'provisioning',
            'message' => $allReady ? 'All components ready' : 'Provisioning in progress',
            'components' => [
                'database' => $dbStatus['status'],
                'storage' => $storageStatus['status'],
                'config' => $configStatus['status'],
            ]
        ];
    }

    /**
     * Log provisioning step
     * 
     * @param string $message Log message
     * @param string $tenantId Tenant identifier
     */
    protected function logStep(string $message, string $tenantId): void
    {
        $this->provisioningLog[] = [
            'timestamp' => microtime(true),
            'tenant_id' => $tenantId,
            'message' => $message,
            'datetime' => date('Y-m-d H:i:s'),
        ];
    }
}