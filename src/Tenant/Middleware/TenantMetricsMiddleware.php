<?php

namespace Ludelix\Tenant\Middleware;

use Ludelix\Tenant\Core\TenantManager;
use Ludelix\Tenant\Analytics\TenantMetrics;
use Ludelix\Tenant\Analytics\UsageTracker;
use Ludelix\Tenant\Analytics\PerformanceMonitor;
use Ludelix\PRT\Request;
use Ludelix\PRT\Response;

/**
 * Tenant Metrics Middleware - Automatic Metrics Collection
 * 
 * Automatically collects tenant metrics, usage data, and performance information
 * for all requests passing through the middleware pipeline.
 * 
 * @package Ludelix\Tenant\Middleware
 * @author Ludelix Framework Team
 * @version 1.0.0
 * @since 1.0.0
 */
class TenantMetricsMiddleware
{
    /**
     * Tenant manager instance
     */
    protected TenantManager $tenantManager;

    /**
     * Tenant metrics collector
     */
    protected TenantMetrics $tenantMetrics;

    /**
     * Usage tracker
     */
    protected UsageTracker $usageTracker;

    /**
     * Performance monitor
     */
    protected PerformanceMonitor $performanceMonitor;

    /**
     * Middleware configuration
     */
    protected array $config;

    /**
     * Initialize tenant metrics middleware
     * 
     * @param TenantManager $tenantManager Tenant management system
     * @param TenantMetrics $tenantMetrics Metrics collector
     * @param UsageTracker $usageTracker Usage tracking system
     * @param PerformanceMonitor $performanceMonitor Performance monitoring
     * @param array $config Middleware configuration
     */
    public function __construct(
        TenantManager $tenantManager,
        TenantMetrics $tenantMetrics,
        UsageTracker $usageTracker,
        PerformanceMonitor $performanceMonitor,
        array $config = []
    ) {
        $this->tenantManager = $tenantManager;
        $this->tenantMetrics = $tenantMetrics;
        $this->usageTracker = $usageTracker;
        $this->performanceMonitor = $performanceMonitor;
        $this->config = array_merge([
            'collect_metrics' => true,
            'track_usage' => true,
            'monitor_performance' => true,
            'sample_rate' => 1.0,
            'skip_routes' => ['/health', '/metrics'],
            'track_bandwidth' => true,
            'track_api_calls' => true,
        ], $config);
    }

    /**
     * Handle incoming request with metrics collection
     * 
     * @param Request $request Incoming HTTP request
     * @param callable $next Next middleware in pipeline
     * @return Response HTTP response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Skip metrics collection for certain routes
        if ($this->shouldSkipMetrics($request)) {
            return $next($request);
        }

        // Skip if sampling rate doesn't match
        if (!$this->shouldSample()) {
            return $next($request);
        }

        $tenant = $this->tenantManager->current();
        
        if (!$tenant) {
            return $next($request);
        }

        // Set tenant context for all collectors
        $this->setTenantContext($tenant);

        // Start performance measurement
        $measurementId = null;
        if ($this->config['monitor_performance']) {
            $measurementId = $this->performanceMonitor->startMeasurement(
                'http_request',
                [
                    'method' => $request->getMethod(),
                    'uri' => $request->getUri(),
                    'user_agent' => $request->server('HTTP_USER_AGENT'),
                ]
            );
        }

        $startTime = microtime(true);
        
        try {
            // Process request
            $response = $next($request);
            
            // Collect metrics after successful processing
            $this->collectRequestMetrics($request, $response, $startTime, $measurementId);
            
            return $response;
            
        } catch (\Throwable $e) {
            // Collect error metrics
            $this->collectErrorMetrics($request, $e, $startTime, $measurementId);
            
            throw $e;
        }
    }

    /**
     * Collect metrics for successful request
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     * @param float $startTime Request start time
     * @param string|null $measurementId Performance measurement ID
     */
    protected function collectRequestMetrics(
        Request $request,
        Response $response,
        float $startTime,
        ?string $measurementId
    ): void {
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        $statusCode = $response->getStatusCode();

        // Collect basic metrics
        if ($this->config['collect_metrics']) {
            $this->tenantMetrics->recordResolution(
                $this->tenantManager->current(),
                $request,
                ['middleware_metrics']
            );
        }

        // Track API call usage
        if ($this->config['track_api_calls']) {
            $this->usageTracker->trackApiCall(
                $request->getUri(),
                (int) ($responseTime * 1000)
            );
        }

        // Track bandwidth usage
        if ($this->config['track_bandwidth']) {
            $this->trackBandwidthUsage($request, $response);
        }

        // Record performance metrics
        if ($this->config['monitor_performance'] && $measurementId) {
            $this->performanceMonitor->endMeasurement($measurementId, [
                'status_code' => $statusCode,
                'response_size' => $this->getResponseSize($response),
            ]);

            // Also record HTTP request performance
            $this->performanceMonitor->recordHttpRequest(
                $request->getMethod(),
                $request->getUri(),
                $statusCode,
                $responseTime
            );
        }

        // Track additional usage metrics
        $this->trackAdditionalUsage($request, $response);
    }

    /**
     * Collect metrics for failed request
     * 
     * @param Request $request HTTP request
     * @param \Throwable $exception Exception that occurred
     * @param float $startTime Request start time
     * @param string|null $measurementId Performance measurement ID
     */
    protected function collectErrorMetrics(
        Request $request,
        \Throwable $exception,
        float $startTime,
        ?string $measurementId
    ): void {
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        // Record error in performance monitor
        if ($this->config['monitor_performance'] && $measurementId) {
            $this->performanceMonitor->endMeasurement($measurementId, [
                'error' => true,
                'exception' => get_class($exception),
                'error_message' => $exception->getMessage(),
            ]);

            // Record as failed HTTP request
            $this->performanceMonitor->recordHttpRequest(
                $request->getMethod(),
                $request->getUri(),
                500, // Assume 500 for exceptions
                $responseTime,
                ['error' => true, 'exception' => get_class($exception)]
            );
        }

        // Still track API call (as failed)
        if ($this->config['track_api_calls']) {
            $this->usageTracker->trackApiCall(
                $request->getUri() . ' (ERROR)',
                (int) ($responseTime * 1000)
            );
        }
    }

    /**
     * Track bandwidth usage
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     */
    protected function trackBandwidthUsage(Request $request, Response $response): void
    {
        // Track request bandwidth (upload)
        $requestSize = $this->getRequestSize($request);
        if ($requestSize > 0) {
            $this->usageTracker->trackBandwidth($requestSize, 'upload');
        }

        // Track response bandwidth (download)
        $responseSize = $this->getResponseSize($response);
        if ($responseSize > 0) {
            $this->usageTracker->trackBandwidth($responseSize, 'download');
        }
    }

    /**
     * Track additional usage metrics
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     */
    protected function trackAdditionalUsage(Request $request, Response $response): void
    {
        // Track file uploads
        if ($request->hasFiles()) {
            foreach ($request->files() as $file) {
                $this->usageTracker->trackFileUpload(
                    $file->getSize(),
                    $file->getExtension()
                );
            }
        }

        // Track specific endpoints
        $uri = $request->getUri();
        
        if (str_contains($uri, '/api/')) {
            // API endpoint usage
            $this->trackApiEndpointUsage($request, $response);
        }
        
        if (str_contains($uri, '/admin/')) {
            // Admin panel usage
            $this->trackAdminUsage($request, $response);
        }
    }

    /**
     * Track API endpoint specific usage
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     */
    protected function trackApiEndpointUsage(Request $request, Response $response): void
    {
        // Track API-specific metrics
        $endpoint = $this->extractApiEndpoint($request->getUri());
        $method = $request->getMethod();
        
        // This could track specific API usage patterns
        $this->usageTracker->trackApiCall(
            "{$method} {$endpoint}",
            0 // Response time already tracked elsewhere
        );
    }

    /**
     * Track admin panel usage
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     */
    protected function trackAdminUsage(Request $request, Response $response): void
    {
        // Track admin panel access
        // This could be used for billing admin usage separately
    }

    /**
     * Set tenant context for all collectors
     * 
     * @param object $tenant Current tenant
     */
    protected function setTenantContext(object $tenant): void
    {
        $this->usageTracker->setCurrentTenant($tenant);
        $this->performanceMonitor->setCurrentTenant($tenant);
    }

    /**
     * Check if metrics collection should be skipped
     * 
     * @param Request $request HTTP request
     * @return bool True if should skip
     */
    protected function shouldSkipMetrics(Request $request): bool
    {
        $path = $request->getPath();
        
        foreach ($this->config['skip_routes'] as $skipRoute) {
            if (str_starts_with($path, $skipRoute)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if request should be sampled
     * 
     * @return bool True if should sample
     */
    protected function shouldSample(): bool
    {
        return mt_rand() / mt_getrandmax() <= $this->config['sample_rate'];
    }

    /**
     * Get request size in bytes
     * 
     * @param Request $request HTTP request
     * @return int Request size
     */
    protected function getRequestSize(Request $request): int
    {
        $size = 0;
        
        // Add headers size
        foreach ($request->getHeaders() as $name => $values) {
            $size += strlen($name) + strlen(implode(', ', $values)) + 4; // +4 for ": " and "\r\n"
        }
        
        // Add body size
        $body = $request->getContent();
        if ($body) {
            $size += strlen($body);
        }
        
        return $size;
    }

    /**
     * Get response size in bytes
     * 
     * @param Response $response HTTP response
     * @return int Response size
     */
    protected function getResponseSize(Response $response): int
    {
        $size = 0;
        
        // Add headers size
        foreach ($response->getHeaders() as $name => $values) {
            $size += strlen($name) + strlen(implode(', ', $values)) + 4;
        }
        
        // Add content size
        $content = $response->getContent();
        if ($content) {
            $size += strlen($content);
        }
        
        return $size;
    }

    /**
     * Extract API endpoint from URI
     * 
     * @param string $uri Request URI
     * @return string API endpoint
     */
    protected function extractApiEndpoint(string $uri): string
    {
        // Remove query parameters
        $uri = strtok($uri, '?');
        
        // Extract endpoint pattern (remove IDs and dynamic parts)
        $uri = preg_replace('/\/\d+/', '/{id}', $uri);
        $uri = preg_replace('/\/[a-f0-9\-]{36}/', '/{uuid}', $uri);
        
        return $uri;
    }
}