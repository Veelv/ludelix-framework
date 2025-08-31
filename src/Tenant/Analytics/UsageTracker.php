<?php

namespace Ludelix\Tenant\Analytics;

use Ludelix\Interface\Tenant\TenantInterface;

/**
 * Usage Tracker - Tenant Resource Usage Monitoring
 * 
 * Tracks and monitors tenant resource usage including storage, bandwidth,
 * API calls, database queries, and custom metrics with quota enforcement.
 * 
 * @package Ludelix\Tenant\Analytics
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class UsageTracker
{
    /**
     * Current tenant context
     */
    protected ?TenantInterface $currentTenant = null;

    /**
     * Usage data storage
     */
    protected array $usageData = [];

    /**
     * Usage configuration
     */
    protected array $config;

    /**
     * Tracked metrics
     */
    protected array $trackedMetrics = [
        'storage',
        'bandwidth',
        'api_calls',
        'database_queries',
        'cache_operations',
        'file_uploads',
        'email_sends',
        'background_jobs',
    ];

    /**
     * Initialize usage tracker
     * 
     * @param array $config Tracking configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'storage_path' => 'storage/usage',
            'flush_interval' => 300, // 5 minutes
            'aggregate_daily' => true,
            'quota_warnings' => [75, 90, 95],
        ], $config);
        
        $this->ensureStorageDirectory();
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
     * Track storage usage
     * 
     * @param int $bytes Bytes used
     * @param string $operation Operation type
     * @return self Fluent interface
     */
    public function trackStorage(int $bytes, string $operation = 'usage'): self
    {
        return $this->track('storage', $bytes, ['operation' => $operation]);
    }

    /**
     * Track bandwidth usage
     * 
     * @param int $bytes Bytes transferred
     * @param string $direction Direction (upload/download)
     * @return self Fluent interface
     */
    public function trackBandwidth(int $bytes, string $direction = 'download'): self
    {
        return $this->track('bandwidth', $bytes, ['direction' => $direction]);
    }

    /**
     * Track API call
     * 
     * @param string $endpoint API endpoint
     * @param int $responseTime Response time in milliseconds
     * @return self Fluent interface
     */
    public function trackApiCall(string $endpoint, int $responseTime = 0): self
    {
        return $this->track('api_calls', 1, [
            'endpoint' => $endpoint,
            'response_time' => $responseTime
        ]);
    }

    /**
     * Track database query
     * 
     * @param string $query Query type
     * @param float $executionTime Execution time in seconds
     * @return self Fluent interface
     */
    public function trackDatabaseQuery(string $query, float $executionTime = 0): self
    {
        return $this->track('database_queries', 1, [
            'query_type' => $query,
            'execution_time' => $executionTime
        ]);
    }

    /**
     * Track cache operation
     * 
     * @param string $operation Operation type (hit/miss/set/delete)
     * @param string $key Cache key
     * @return self Fluent interface
     */
    public function trackCacheOperation(string $operation, string $key = ''): self
    {
        return $this->track('cache_operations', 1, [
            'operation' => $operation,
            'key' => $key
        ]);
    }

    /**
     * Track file upload
     * 
     * @param int $fileSize File size in bytes
     * @param string $fileType File type
     * @return self Fluent interface
     */
    public function trackFileUpload(int $fileSize, string $fileType = ''): self
    {
        return $this->track('file_uploads', 1, [
            'file_size' => $fileSize,
            'file_type' => $fileType
        ]);
    }

    /**
     * Track email send
     * 
     * @param string $type Email type
     * @param int $recipients Number of recipients
     * @return self Fluent interface
     */
    public function trackEmailSend(string $type = 'transactional', int $recipients = 1): self
    {
        return $this->track('email_sends', $recipients, ['type' => $type]);
    }

    /**
     * Track background job
     * 
     * @param string $jobType Job type
     * @param float $executionTime Execution time in seconds
     * @return self Fluent interface
     */
    public function trackBackgroundJob(string $jobType, float $executionTime = 0): self
    {
        return $this->track('background_jobs', 1, [
            'job_type' => $jobType,
            'execution_time' => $executionTime
        ]);
    }

    /**
     * Get current usage for tenant
     * 
     * @param string|null $tenantId Tenant ID (null for current)
     * @param string|null $metric Specific metric (null for all)
     * @return array Usage data
     */
    public function getUsage(?string $tenantId = null, ?string $metric = null): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId) {
            return [];
        }

        $usageFile = $this->getUsageFile($targetTenantId);
        $usage = $this->loadUsageData($usageFile);

        if ($metric) {
            return $usage[$metric] ?? [];
        }

        return $usage;
    }

    /**
     * Get usage summary for tenant
     * 
     * @param string|null $tenantId Tenant ID
     * @param string $period Period (daily/weekly/monthly)
     * @return array Usage summary
     */
    public function getUsageSummary(?string $tenantId = null, string $period = 'daily'): array
    {
        $usage = $this->getUsage($tenantId);
        $summary = [];

        foreach ($this->trackedMetrics as $metric) {
            $metricData = $usage[$metric] ?? [];
            $summary[$metric] = $this->aggregateMetricData($metricData, $period);
        }

        return $summary;
    }

    /**
     * Check quota status for tenant
     * 
     * @param string|null $tenantId Tenant ID
     * @return array Quota status
     */
    public function getQuotaStatus(?string $tenantId = null): array
    {
        $targetTenantId = $tenantId ?? $this->currentTenant?->getId();
        
        if (!$targetTenantId || !$this->currentTenant) {
            return [];
        }

        $usage = $this->getUsage($targetTenantId);
        $quotas = $this->currentTenant->getResourceQuotas();
        $status = [];

        foreach ($quotas['quotas'] as $resource => $quota) {
            $currentUsage = $this->calculateCurrentUsage($usage, $resource);
            $quotaBytes = $this->parseQuotaToBytes($quota);
            $percentage = $quotaBytes > 0 ? ($currentUsage / $quotaBytes) * 100 : 0;

            $status[$resource] = [
                'quota' => $quota,
                'quota_bytes' => $quotaBytes,
                'used' => $currentUsage,
                'used_formatted' => $this->formatBytes($currentUsage),
                'percentage' => round($percentage, 2),
                'status' => $this->getQuotaStatusLevel($percentage),
                'warnings' => $this->getQuotaWarnings($percentage),
            ];
        }

        return $status;
    }

    /**
     * Get usage trends
     * 
     * @param string|null $tenantId Tenant ID
     * @param string $metric Metric to analyze
     * @param int $days Number of days
     * @return array Trend data
     */
    public function getUsageTrends(?string $tenantId = null, string $metric = 'api_calls', int $days = 30): array
    {
        $usage = $this->getUsage($tenantId, $metric);
        $trends = [];
        $cutoffTime = time() - ($days * 24 * 60 * 60);

        foreach ($usage as $entry) {
            if ($entry['timestamp'] >= $cutoffTime) {
                $date = date('Y-m-d', $entry['timestamp']);
                $trends[$date] = ($trends[$date] ?? 0) + $entry['value'];
            }
        }

        ksort($trends);
        return $trends;
    }

    /**
     * Core tracking method
     * 
     * @param string $metric Metric name
     * @param mixed $value Metric value
     * @param array $context Additional context
     * @return self Fluent interface
     */
    protected function track(string $metric, mixed $value, array $context = []): self
    {
        if (!$this->currentTenant) {
            return $this;
        }

        $tenantId = $this->currentTenant->getId();
        $entry = [
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'metric' => $metric,
            'value' => $value,
            'context' => $context,
        ];

        // Add to in-memory buffer
        if (!isset($this->usageData[$tenantId])) {
            $this->usageData[$tenantId] = [];
        }
        if (!isset($this->usageData[$tenantId][$metric])) {
            $this->usageData[$tenantId][$metric] = [];
        }

        $this->usageData[$tenantId][$metric][] = $entry;

        // Flush if needed
        $this->flushIfNeeded($tenantId);

        return $this;
    }

    /**
     * Flush usage data to storage
     * 
     * @param string $tenantId Tenant ID
     */
    protected function flushIfNeeded(string $tenantId): void
    {
        $totalEntries = 0;
        foreach ($this->usageData[$tenantId] ?? [] as $metric => $entries) {
            $totalEntries += count($entries);
        }

        // Flush if buffer is large or time interval passed
        if ($totalEntries >= 100 || $this->shouldFlushByTime($tenantId)) {
            $this->flushUsageData($tenantId);
        }
    }

    /**
     * Flush usage data to file
     * 
     * @param string $tenantId Tenant ID
     */
    protected function flushUsageData(string $tenantId): void
    {
        if (!isset($this->usageData[$tenantId])) {
            return;
        }

        $usageFile = $this->getUsageFile($tenantId);
        $existingData = $this->loadUsageData($usageFile);

        // Merge with existing data
        foreach ($this->usageData[$tenantId] as $metric => $entries) {
            if (!isset($existingData[$metric])) {
                $existingData[$metric] = [];
            }
            $existingData[$metric] = array_merge($existingData[$metric], $entries);
        }

        // Save to file
        file_put_contents($usageFile, json_encode($existingData));

        // Clear buffer
        unset($this->usageData[$tenantId]);
    }

    /**
     * Load usage data from file
     * 
     * @param string $usageFile Usage file path
     * @return array Usage data
     */
    protected function loadUsageData(string $usageFile): array
    {
        if (!file_exists($usageFile)) {
            return [];
        }

        $data = json_decode(file_get_contents($usageFile), true);
        return is_array($data) ? $data : [];
    }

    /**
     * Get usage file path for tenant
     * 
     * @param string $tenantId Tenant ID
     * @return string Usage file path
     */
    protected function getUsageFile(string $tenantId): string
    {
        return $this->config['storage_path'] . "/usage_{$tenantId}.json";
    }

    /**
     * Check if should flush by time
     * 
     * @param string $tenantId Tenant ID
     * @return bool True if should flush
     */
    protected function shouldFlushByTime(string $tenantId): bool
    {
        $usageFile = $this->getUsageFile($tenantId);
        
        if (!file_exists($usageFile)) {
            return true;
        }

        return (time() - filemtime($usageFile)) >= $this->config['flush_interval'];
    }

    /**
     * Aggregate metric data by period
     * 
     * @param array $metricData Metric data
     * @param string $period Period type
     * @return array Aggregated data
     */
    protected function aggregateMetricData(array $metricData, string $period): array
    {
        $aggregated = [];
        
        foreach ($metricData as $entry) {
            $key = match($period) {
                'hourly' => date('Y-m-d H', $entry['timestamp']),
                'daily' => date('Y-m-d', $entry['timestamp']),
                'weekly' => date('Y-W', $entry['timestamp']),
                'monthly' => date('Y-m', $entry['timestamp']),
                default => date('Y-m-d', $entry['timestamp'])
            };
            
            $aggregated[$key] = ($aggregated[$key] ?? 0) + $entry['value'];
        }

        return $aggregated;
    }

    /**
     * Calculate current usage for resource
     * 
     * @param array $usage Usage data
     * @param string $resource Resource type
     * @return int Current usage in bytes
     */
    protected function calculateCurrentUsage(array $usage, string $resource): int
    {
        $resourceData = $usage[$resource] ?? [];
        $total = 0;

        foreach ($resourceData as $entry) {
            $total += $entry['value'];
        }

        return $total;
    }

    /**
     * Parse quota string to bytes
     * 
     * @param string $quota Quota string
     * @return int Bytes
     */
    protected function parseQuotaToBytes(string $quota): int
    {
        if (is_numeric($quota)) {
            return (int) $quota;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(GB|MB|KB|TB)$/i', $quota, $matches)) {
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            
            return (int) match($unit) {
                'KB' => $number * 1024,
                'MB' => $number * 1024 * 1024,
                'GB' => $number * 1024 * 1024 * 1024,
                'TB' => $number * 1024 * 1024 * 1024 * 1024,
                default => $number
            };
        }

        return 0;
    }

    /**
     * Format bytes to human readable
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get quota status level
     * 
     * @param float $percentage Usage percentage
     * @return string Status level
     */
    protected function getQuotaStatusLevel(float $percentage): string
    {
        if ($percentage >= 100) return 'exceeded';
        if ($percentage >= 95) return 'critical';
        if ($percentage >= 90) return 'warning';
        if ($percentage >= 75) return 'caution';
        return 'normal';
    }

    /**
     * Get quota warnings
     * 
     * @param float $percentage Usage percentage
     * @return array Warnings
     */
    protected function getQuotaWarnings(float $percentage): array
    {
        $warnings = [];
        
        foreach ($this->config['quota_warnings'] as $threshold) {
            if ($percentage >= $threshold) {
                $warnings[] = "Usage is at {$percentage}% (threshold: {$threshold}%)";
            }
        }

        return $warnings;
    }

    /**
     * Ensure storage directory exists
     */
    protected function ensureStorageDirectory(): void
    {
        if (!is_dir($this->config['storage_path'])) {
            mkdir($this->config['storage_path'], 0755, true);
        }
    }
}