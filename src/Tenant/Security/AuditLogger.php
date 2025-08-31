<?php

namespace Ludelix\Tenant\Security;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Audit Logger - Tenant Security Audit Trail System
 * 
 * Provides comprehensive audit logging for tenant operations, security events,
 * and compliance tracking with structured logging and retention policies.
 * 
 * @package Ludelix\Tenant\Security
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class AuditLogger
{
    /**
     * Audit log storage path
     */
    protected string $logPath;

    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Audit configuration
     */
    protected array $config;

    /**
     * In-memory audit log buffer
     */
    protected array $logBuffer = [];

    /**
     * Log levels
     */
    protected const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
    ];

    /**
     * Initialize audit logger
     * 
     * @param array $config Audit logging configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'log_path' => 'logs/audit',
            'buffer_size' => 100,
            'auto_flush' => true,
            'log_level' => 'INFO',
            'retention_days' => 365,
            'include_stack_trace' => false,
        ], $config);
        
        $this->logPath = $this->config['log_path'];
        $this->ensureLogDirectory();
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
     * Log tenant access event
     * 
     * @param string $action Action performed
     * @param array $context Additional context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logAccess(string $action, array $context = [], string $level = 'INFO'): self
    {
        return $this->log('tenant_access', $action, $context, $level);
    }

    /**
     * Log security event
     * 
     * @param string $event Security event type
     * @param array $context Event context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logSecurityEvent(string $event, array $context = [], string $level = 'WARNING'): self
    {
        return $this->log('security_event', $event, $context, $level);
    }

    /**
     * Log data operation
     * 
     * @param string $operation Data operation type
     * @param array $context Operation context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logDataOperation(string $operation, array $context = [], string $level = 'INFO'): self
    {
        return $this->log('data_operation', $operation, $context, $level);
    }

    /**
     * Log authentication event
     * 
     * @param string $event Authentication event
     * @param array $context Event context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logAuthentication(string $event, array $context = [], string $level = 'INFO'): self
    {
        return $this->log('authentication', $event, $context, $level);
    }

    /**
     * Log authorization event
     * 
     * @param string $event Authorization event
     * @param array $context Event context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logAuthorization(string $event, array $context = [], string $level = 'WARNING'): self
    {
        return $this->log('authorization', $event, $context, $level);
    }

    /**
     * Log configuration change
     * 
     * @param string $change Configuration change
     * @param array $context Change context
     * @param string $level Log level
     * @return self Fluent interface
     */
    public function logConfigChange(string $change, array $context = [], string $level = 'INFO'): self
    {
        return $this->log('config_change', $change, $context, $level);
    }

    /**
     * Get audit logs for tenant
     * 
     * @param string|null $tenantId Tenant ID (null for current)
     * @param array $filters Log filters
     * @return array Audit logs
     */
    public function getLogs(?string $tenantId = null, array $filters = []): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }

        $logFile = $this->getLogFile($targetTenantId);
        
        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $handle = fopen($logFile, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $logEntry = json_decode(trim($line), true);
                
                if ($logEntry && $this->matchesFilters($logEntry, $filters)) {
                    $logs[] = $logEntry;
                }
            }
            fclose($handle);
        }

        return array_reverse($logs); // Most recent first
    }

    /**
     * Get audit statistics
     * 
     * @param string|null $tenantId Tenant ID
     * @param array $options Statistics options
     * @return array Audit statistics
     */
    public function getStatistics(?string $tenantId = null, array $options = []): array
    {
        $logs = $this->getLogs($tenantId, $options);
        
        $stats = [
            'total_events' => count($logs),
            'events_by_type' => [],
            'events_by_level' => [],
            'events_by_date' => [],
            'security_events' => 0,
            'failed_operations' => 0,
        ];

        foreach ($logs as $log) {
            // Count by type
            $type = $log['type'] ?? 'unknown';
            $stats['events_by_type'][$type] = ($stats['events_by_type'][$type] ?? 0) + 1;
            
            // Count by level
            $level = $log['level'] ?? 'INFO';
            $stats['events_by_level'][$level] = ($stats['events_by_level'][$level] ?? 0) + 1;
            
            // Count by date
            $date = date('Y-m-d', $log['timestamp']);
            $stats['events_by_date'][$date] = ($stats['events_by_date'][$date] ?? 0) + 1;
            
            // Count security events
            if ($type === 'security_event') {
                $stats['security_events']++;
            }
            
            // Count failed operations
            if (in_array($level, ['ERROR', 'CRITICAL'])) {
                $stats['failed_operations']++;
            }
        }

        return $stats;
    }

    /**
     * Flush log buffer to disk
     * 
     * @return self Fluent interface
     */
    public function flush(): self
    {
        if (empty($this->logBuffer)) {
            return $this;
        }

        // Group logs by tenant
        $logsByTenant = [];
        foreach ($this->logBuffer as $logEntry) {
            $tenantId = $logEntry['tenant_id'] ?? 'system';
            $logsByTenant[$tenantId][] = $logEntry;
        }

        // Write logs to respective files
        foreach ($logsByTenant as $tenantId => $logs) {
            $this->writeLogsToFile($tenantId, $logs);
        }

        $this->logBuffer = [];
        return $this;
    }

    /**
     * Clear old audit logs based on retention policy
     * 
     * @return int Number of files cleaned
     */
    public function cleanup(): int
    {
        $retentionDays = $this->config['retention_days'];
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $cleanedFiles = 0;

        $logFiles = glob($this->logPath . '/*.log');
        
        foreach ($logFiles as $logFile) {
            if (filemtime($logFile) < $cutoffTime) {
                unlink($logFile);
                $cleanedFiles++;
            }
        }

        return $cleanedFiles;
    }

    /**
     * Core logging method
     * 
     * @param string $type Log type
     * @param string $message Log message
     * @param array $context Log context
     * @param string $level Log level
     * @return self Fluent interface
     */
    protected function log(string $type, string $message, array $context, string $level): self
    {
        if (!$this->shouldLog($level)) {
            return $this;
        }

        $logEntry = [
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'level' => $level,
            'type' => $type,
            'message' => $message,
            'tenant_id' => $this->currentTenant?->getId(),
            'tenant_name' => $this->currentTenant?->getName(),
            'context' => $context,
        ];

        // Add stack trace if configured
        if ($this->config['include_stack_trace'] && in_array($level, ['ERROR', 'CRITICAL'])) {
            $logEntry['stack_trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $this->logBuffer[] = $logEntry;

        // Auto-flush if buffer is full or auto-flush is enabled
        if (count($this->logBuffer) >= $this->config['buffer_size'] || $this->config['auto_flush']) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Check if log level should be logged
     * 
     * @param string $level Log level
     * @return bool True if should log
     */
    protected function shouldLog(string $level): bool
    {
        $currentLevel = self::LEVELS[$this->config['log_level']] ?? 1;
        $messageLevel = self::LEVELS[$level] ?? 1;
        
        return $messageLevel >= $currentLevel;
    }

    /**
     * Get log file path for tenant
     * 
     * @param string $tenantId Tenant ID
     * @return string Log file path
     */
    protected function getLogFile(string $tenantId): string
    {
        return $this->logPath . "/tenant_{$tenantId}.log";
    }

    /**
     * Write logs to file
     * 
     * @param string $tenantId Tenant ID
     * @param array $logs Log entries
     */
    protected function writeLogsToFile(string $tenantId, array $logs): void
    {
        $logFile = $this->getLogFile($tenantId);
        $handle = fopen($logFile, 'a');
        
        if ($handle) {
            foreach ($logs as $logEntry) {
                fwrite($handle, json_encode($logEntry) . "\n");
            }
            fclose($handle);
        }
    }

    /**
     * Check if log entry matches filters
     * 
     * @param array $logEntry Log entry
     * @param array $filters Filters to apply
     * @return bool True if matches
     */
    protected function matchesFilters(array $logEntry, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($logEntry[$key]) || $logEntry[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Ensure log directory exists
     */
    protected function ensureLogDirectory(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Destructor - flush remaining logs
     */
    public function __destruct()
    {
        $this->flush();
    }
}